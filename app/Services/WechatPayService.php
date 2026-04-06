<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentChannel;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class WechatPayService
{
    private bool $channelTableResolved = false;
    private bool $channelTableExists = false;
    private bool $channelResolved = false;
    private ?PaymentChannel $resolvedChannel = null;

    public function isConfigured(?PaymentChannel $channel = null): bool
    {
        return $this->appId($channel) !== ''
            && $this->mchId($channel) !== ''
            && $this->apiV3Key($channel) !== ''
            && $this->serialNo($channel) !== ''
            && $this->privateKey($channel) !== ''
            && $this->platformCertificate($channel) !== '';
    }

    public function appId(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('app_id', (string) config('payments.wechat.app_id'), $channel);
    }

    public function mchId(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('mch_id', (string) config('payments.wechat.mch_id'), $channel);
    }

    public function apiV3Key(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('api_v3_key', (string) config('payments.wechat.api_v3_key'), $channel);
    }

    public function serialNo(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('serial_no', (string) config('payments.wechat.serial_no'), $channel);
    }

    public function gateway(): string
    {
        return trim((string) config('payments.wechat.gateway')) ?: 'https://api.mch.weixin.qq.com';
    }

    public function createNative(Order $order, ?PaymentChannel $channel = null): array
    {
        if (! $this->isConfigured($channel)) {
            throw new RuntimeException('WeChat Pay is not configured.');
        }

        $resolvedChannel = $this->resolveChannel($channel);
        $notifyUrl = route('payments.notify', ['provider' => 'wechat']);

        if ($resolvedChannel) {
            $notifyUrl .= '?channel=' . $resolvedChannel->getKey();
        }

        $payload = $this->request(
            'POST',
            '/v3/pay/transactions/native',
            [
                'appid' => $this->appId($channel),
                'mchid' => $this->mchId($channel),
                'description' => $this->buildSubject($order),
                'out_trade_no' => $order->order_no,
                'notify_url' => $notifyUrl,
                'time_expire' => now('Asia/Shanghai')
                    ->addMinutes(max(1, (int) config('payments.wechat.timeout_minutes', 15)))
                    ->toIso8601String(),
                'amount' => [
                    'total' => $this->amountInFen($order),
                    'currency' => trim((string) config('payments.wechat.currency', 'CNY')) ?: 'CNY',
                ],
            ],
            $channel,
        );

        $qrCode = trim((string) ($payload['code_url'] ?? ''));

        if ($qrCode === '') {
            throw new RuntimeException($this->responseErrorMessage($payload, 'WeChat Native order failed.'));
        }

        return [
            'qr_code' => $qrCode,
            'payload' => $payload,
        ];
    }

    public function queryTrade(Order $order, ?PaymentChannel $channel = null): array
    {
        if (! $this->isConfigured($channel)) {
            throw new RuntimeException('WeChat Pay is not configured.');
        }

        return $this->request(
            'GET',
            '/v3/pay/transactions/out-trade-no/' . rawurlencode($order->order_no),
            null,
            $channel,
            ['mchid' => $this->mchId($channel)],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseNotification(Request $request, ?PaymentChannel $channel = null): ?array
    {
        if (! $this->isConfigured($channel)) {
            return null;
        }

        $timestamp = trim((string) $request->header('Wechatpay-Timestamp'));
        $nonce = trim((string) $request->header('Wechatpay-Nonce'));
        $signature = trim((string) $request->header('Wechatpay-Signature'));
        $body = (string) $request->getContent();

        if ($timestamp === '' || $nonce === '' || $signature === '' || $body === '') {
            return null;
        }

        if (! $this->verifySignature($timestamp, $nonce, $body, $signature, $channel)) {
            return null;
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            return null;
        }

        $resource = $payload['resource'] ?? null;
        if (! is_array($resource)) {
            return null;
        }

        $decryptedResource = $this->decryptResource($resource, $channel);
        if (! is_array($decryptedResource)) {
            return null;
        }

        $payload['resource'] = $decryptedResource;

        return $payload;
    }

    public function channelForOrder(Order $order, bool $allowDisabled = true): ?PaymentChannel
    {
        if (! $this->hasChannelTable()) {
            return $this->defaultChannel();
        }

        $payload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $selectedChannelId = (int) ($payload['selected_channel_id'] ?? 0);

        if ($selectedChannelId > 0) {
            $query = PaymentChannel::query()
                ->whereKey($selectedChannelId)
                ->where('provider', 'wechat');

            if (! $allowDisabled) {
                $query->where('is_enabled', true);
            }

            $channel = $query->first();
            if ($channel) {
                return $channel;
            }
        }

        return $this->defaultChannel();
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $path,
        ?array $body = null,
        ?PaymentChannel $channel = null,
        array $query = [],
    ): array {
        $jsonBody = $body !== null
            ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
        $requestPath = $path;

        if ($query !== []) {
            $requestPath .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => $this->buildAuthorization($method, $requestPath, $jsonBody, $channel),
            'User-Agent' => 'FlowX/1.0',
        ])->timeout(15);

        if ($jsonBody !== '') {
            $request = $request->withBody($jsonBody, 'application/json');
        }

        $response = $request->send(strtoupper($method), rtrim($this->gateway(), '/') . $requestPath);
        $responseBody = (string) $response->body();

        if (! $this->verifyResponseSignature($response, $responseBody, $channel)) {
            throw new RuntimeException('Invalid WeChat Pay response signature.');
        }

        $decoded = $responseBody !== '' ? json_decode($responseBody, true) : [];
        if ($responseBody !== '' && ! is_array($decoded)) {
            throw new RuntimeException('Invalid WeChat Pay response.');
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->responseErrorMessage($decoded, trim($responseBody) ?: 'WeChat Pay request failed.'));
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function buildAuthorization(string $method, string $requestPath, string $body, ?PaymentChannel $channel = null): string
    {
        $timestamp = (string) time();
        $nonce = Str::random(32);
        $message = strtoupper($method) . "\n{$requestPath}\n{$timestamp}\n{$nonce}\n{$body}\n";
        $signature = $this->signMessage($message, $channel);

        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%s",serial_no="%s"',
            $this->mchId($channel),
            $nonce,
            $signature,
            $timestamp,
            $this->serialNo($channel),
        );
    }

    private function signMessage(string $message, ?PaymentChannel $channel = null): string
    {
        $privateKey = openssl_pkey_get_private($this->privateKeyPem($channel));

        if ($privateKey === false) {
            throw new RuntimeException('Invalid WeChat Pay private key.');
        }

        $signature = '';
        if (! openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign WeChat Pay request.');
        }

        return base64_encode($signature);
    }

    private function verifyResponseSignature(HttpResponse $response, string $body, ?PaymentChannel $channel = null): bool
    {
        $timestamp = trim((string) $response->header('Wechatpay-Timestamp'));
        $nonce = trim((string) $response->header('Wechatpay-Nonce'));
        $signature = trim((string) $response->header('Wechatpay-Signature'));

        if ($timestamp === '' || $nonce === '' || $signature === '') {
            return true;
        }

        return $this->verifySignature($timestamp, $nonce, $body, $signature, $channel);
    }

    private function verifySignature(string $timestamp, string $nonce, string $body, string $signature, ?PaymentChannel $channel = null): bool
    {
        $publicKey = $this->platformPublicKey($channel);

        if ($publicKey === false) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";

        return openssl_verify($message, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>|null
     */
    private function decryptResource(array $resource, ?PaymentChannel $channel = null): ?array
    {
        $ciphertext = trim((string) ($resource['ciphertext'] ?? ''));
        $nonce = trim((string) ($resource['nonce'] ?? ''));
        $associatedData = (string) ($resource['associated_data'] ?? '');

        if ($ciphertext === '' || $nonce === '') {
            return null;
        }

        $decodedCiphertext = base64_decode($ciphertext, true);
        if ($decodedCiphertext === false || strlen($decodedCiphertext) < 17) {
            return null;
        }

        $authTag = substr($decodedCiphertext, -16);
        $encrypted = substr($decodedCiphertext, 0, -16);
        $plaintext = openssl_decrypt(
            $encrypted,
            'aes-256-gcm',
            $this->apiV3Key($channel),
            OPENSSL_RAW_DATA,
            $nonce,
            $authTag,
            $associatedData,
        );

        if ($plaintext === false) {
            return null;
        }

        $decoded = json_decode($plaintext, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function amountInFen(Order $order): int
    {
        return (int) round(((float) $order->amount) * 100);
    }

    private function buildSubject(Order $order): string
    {
        $order->loadMissing('product');
        $name = trim((string) ($order->product?->name ?? 'Order'));
        $name = $name !== '' ? $name : 'Order';

        return mb_strimwidth($name, 0, 120, '', 'UTF-8');
    }

    private function channelConfigValue(string $key, string $fallback = '', ?PaymentChannel $channel = null): string
    {
        $resolvedChannel = $this->resolveChannel($channel);

        if ($resolvedChannel) {
            return $resolvedChannel->configValue($key, $fallback);
        }

        if ($this->hasChannelTable()) {
            return '';
        }

        return trim($fallback);
    }

    private function resolveChannel(?PaymentChannel $channel = null): ?PaymentChannel
    {
        return $channel ?? $this->defaultChannel();
    }

    private function defaultChannel(): ?PaymentChannel
    {
        if ($this->channelResolved) {
            return $this->resolvedChannel;
        }

        $this->channelResolved = true;

        if (! $this->hasChannelTable()) {
            return null;
        }

        $this->resolvedChannel = PaymentChannel::query()
            ->where('provider', 'wechat')
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        return $this->resolvedChannel;
    }

    private function hasChannelTable(): bool
    {
        if ($this->channelTableResolved) {
            return $this->channelTableExists;
        }

        $this->channelTableResolved = true;
        $this->channelTableExists = Schema::hasTable('payment_channels');

        return $this->channelTableExists;
    }

    private function privateKey(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('private_key', (string) config('payments.wechat.private_key'), $channel);
    }

    private function privateKeyPem(?PaymentChannel $channel = null): string
    {
        return $this->normalizePem($this->privateKey($channel), 'PRIVATE KEY');
    }

    private function platformCertificate(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('platform_certificate', (string) config('payments.wechat.platform_certificate'), $channel);
    }

    private function platformPublicKey(?PaymentChannel $channel = null)
    {
        $value = trim($this->platformCertificate($channel));

        if ($value === '') {
            return false;
        }

        if (str_contains($value, 'BEGIN CERTIFICATE') || str_contains($value, 'BEGIN PUBLIC KEY')) {
            $resource = openssl_pkey_get_public($value);
            if ($resource !== false) {
                return $resource;
            }
        }

        $resource = openssl_pkey_get_public($this->normalizePem($value, 'PUBLIC KEY'));
        if ($resource !== false) {
            return $resource;
        }

        return openssl_pkey_get_public($this->normalizePem($value, 'CERTIFICATE'));
    }

    private function normalizePem(string $value, string $type): string
    {
        if (str_contains($value, 'BEGIN ')) {
            return $value;
        }

        $normalized = preg_replace('/-----BEGIN [^-]+-----|-----END [^-]+-----|\s+/', '', $value) ?? '';
        $chunks = trim(chunk_split($normalized, 64, "\n"));

        return "-----BEGIN {$type}-----\n{$chunks}\n-----END {$type}-----";
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function responseErrorMessage(?array $payload, string $fallback): string
    {
        if (! is_array($payload)) {
            return $fallback;
        }

        foreach (['message', 'detail', 'code'] as $key) {
            $value = trim((string) ($payload[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return $fallback;
    }
}
