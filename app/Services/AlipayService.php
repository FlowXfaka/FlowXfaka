<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AlipayService
{
    private bool $channelTableResolved = false;
    private bool $channelTableExists = false;
    private bool $channelResolved = false;
    private ?PaymentChannel $resolvedChannel = null;

    public function isConfigured(?PaymentChannel $channel = null): bool
    {
        return $this->appId($channel) !== ''
            && $this->publicKey($channel) !== ''
            && $this->privateKey($channel) !== '';
    }

    public function usesScanMode(?PaymentChannel $channel = null): bool
    {
        $resolvedChannel = $this->resolveChannel($channel);

        return $resolvedChannel?->payment_method === 'scan';
    }

    public function appId(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('app_id', (string) config('payments.alipay.app_id'), $channel);
    }

    public function gateway(): string
    {
        return trim((string) config('payments.alipay.gateway')) ?: 'https://openapi.alipay.com/gateway.do';
    }

    public function buildPayHtml(Order $order, bool $mobile = false, ?PaymentChannel $channel = null): string
    {
        $params = $this->buildRequestParameters($order, $mobile, $channel);
        $action = htmlspecialchars($this->gateway(), ENT_QUOTES, 'UTF-8');

        $inputs = '';
        foreach ($params as $name => $value) {
            $inputs .= '<input type="hidden" name="'.htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8').'" value="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'">';
        }

        return '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redirecting</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;color:#1f2937;font-family:"Microsoft YaHei",sans-serif}.pay-redirect{padding:2rem 2.25rem;border-radius:1rem;background:#fff;box-shadow:0 1rem 2rem rgba(15,23,42,.08);text-align:center}strong{display:block;font-size:1.1rem;margin-bottom:.6rem}</style></head><body><div class="pay-redirect"><strong>Redirecting to Alipay</strong><span>If the page does not jump automatically, use the button below.</span><form id="alipay-submit" method="GET" action="'.$action.'">'.$inputs.'<noscript><button type="submit">Continue</button></noscript></form></div><script>document.getElementById("alipay-submit").submit();</script></body></html>';
    }

    public function createPrecreate(Order $order, ?PaymentChannel $channel = null): array
    {
        if (! $this->isConfigured($channel)) {
            throw new RuntimeException('Alipay is not configured.');
        }

        $params = [
            'app_id' => $this->appId($channel),
            'method' => 'alipay.trade.precreate',
            'format' => 'JSON',
            'charset' => (string) config('payments.alipay.charset', 'UTF-8'),
            'sign_type' => (string) config('payments.alipay.sign_type', 'RSA2'),
            'timestamp' => now('Asia/Shanghai')->format('Y-m-d H:i:s'),
            'version' => (string) config('payments.alipay.version', '1.0'),
            'notify_url' => route('payments.notify', ['provider' => 'alipay']),
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
                'total_amount' => number_format((float) $order->amount, 2, '.', ''),
                'subject' => $this->buildSubject($order),
                'timeout_express' => (string) config('payments.alipay.timeout_express', '15m'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $payload = $this->sendSignedRequest($params, $channel);
        $result = $payload['alipay_trade_precreate_response'] ?? null;

        if (! is_array($result)) {
            throw new RuntimeException('Invalid Alipay response.');
        }

        if (($result['code'] ?? '') !== '10000' || empty($result['qr_code'])) {
            throw new RuntimeException((string) ($result['sub_msg'] ?? $result['msg'] ?? 'Alipay precreate failed.'));
        }

        return [
            'qr_code' => (string) $result['qr_code'],
            'payload' => $result,
        ];
    }

    public function queryTrade(Order $order, ?PaymentChannel $channel = null): array
    {
        if (! $this->isConfigured($channel)) {
            throw new RuntimeException('Alipay is not configured.');
        }

        $params = [
            'app_id' => $this->appId($channel),
            'method' => 'alipay.trade.query',
            'format' => 'JSON',
            'charset' => (string) config('payments.alipay.charset', 'UTF-8'),
            'sign_type' => (string) config('payments.alipay.sign_type', 'RSA2'),
            'timestamp' => now('Asia/Shanghai')->format('Y-m-d H:i:s'),
            'version' => (string) config('payments.alipay.version', '1.0'),
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $payload = $this->sendSignedRequest($params, $channel);
        $result = $payload['alipay_trade_query_response'] ?? null;

        if (! is_array($result)) {
            throw new RuntimeException('Invalid Alipay response.');
        }

        return $result;
    }

    public function verify(array $payload, ?PaymentChannel $channel = null): bool
    {
        $signature = (string) ($payload['sign'] ?? '');
        if ($signature === '' || ! $this->isConfigured($channel)) {
            return false;
        }

        unset($payload['sign']);
        $content = $this->buildSignContent($payload);
        $publicKey = openssl_pkey_get_public($this->publicKeyPem($channel));

        if ($publicKey === false) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        if (openssl_verify($content, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1) {
            return true;
        }

        // Some Alipay callback payloads are signed without sign_type.
        unset($payload['sign_type']);
        $fallbackContent = $this->buildSignContent($payload);

        return $fallbackContent !== $content
            && openssl_verify($fallbackContent, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function buildRequestParameters(Order $order, bool $mobile = false, ?PaymentChannel $channel = null): array
    {
        if (! $this->isConfigured($channel)) {
            throw new RuntimeException('Alipay is not configured.');
        }

        $method = $mobile ? 'alipay.trade.wap.pay' : 'alipay.trade.page.pay';
        $productCode = $mobile ? 'QUICK_WAP_WAY' : 'FAST_INSTANT_TRADE_PAY';
        $params = [
            'app_id' => $this->appId($channel),
            'method' => $method,
            'format' => 'JSON',
            'charset' => (string) config('payments.alipay.charset', 'UTF-8'),
            'sign_type' => (string) config('payments.alipay.sign_type', 'RSA2'),
            'timestamp' => now('Asia/Shanghai')->format('Y-m-d H:i:s'),
            'version' => (string) config('payments.alipay.version', '1.0'),
            'notify_url' => route('payments.notify', ['provider' => 'alipay']),
            'return_url' => route('payments.return', ['provider' => 'alipay']),
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
                'product_code' => $productCode,
                'total_amount' => number_format((float) $order->amount, 2, '.', ''),
                'subject' => $this->buildSubject($order),
                'timeout_express' => (string) config('payments.alipay.timeout_express', '15m'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $params['sign'] = $this->sign($params, $channel);

        return $params;
    }

    public function channelForOrder(Order $order, bool $allowDisabled = true): ?PaymentChannel
    {
        if (! $this->hasChannelTable()) {
            return $this->defaultChannel();
        }

        $payload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $selectedChannelId = (int) ($payload['selected_channel_id'] ?? 0);

        if ($selectedChannelId > 0) {
            $query = PaymentChannel::query()->whereKey($selectedChannelId);
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

    private function sendSignedRequest(array $params, ?PaymentChannel $channel = null): array
    {
        $params['sign'] = $this->sign($params, $channel);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $response = Http::withBody('', 'application/x-www-form-urlencoded')
            ->timeout(15)
            ->send('POST', $this->gateway().'?'.$query);

        if (! $response->ok()) {
            throw new RuntimeException('Unable to request Alipay gateway.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid Alipay response.');
        }

        return $payload;
    }

    private function enabledFlag(?PaymentChannel $channel = null): bool
    {
        $resolvedChannel = $this->resolveChannel($channel);

        if ($resolvedChannel) {
            return (bool) $resolvedChannel->is_enabled;
        }

        if ($this->hasChannelTable()) {
            return $this->defaultChannel() !== null;
        }

        return (bool) config('payments.alipay.enabled');
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
            ->where('provider', 'alipay')
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

    private function sign(array $params, ?PaymentChannel $channel = null): string
    {
        $content = $this->buildSignContent($params);
        $privateKey = openssl_pkey_get_private($this->privateKeyPem($channel));

        if ($privateKey === false) {
            throw new RuntimeException('Invalid Alipay private key.');
        }

        $signature = '';
        if (! openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign Alipay request.');
        }

        return base64_encode($signature);
    }

    private function buildSignContent(array $params): string
    {
        ksort($params);
        $pairs = [];

        foreach ($params as $key => $value) {
            if ($key === 'sign' || $value === null || $value === '') {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $pairs[] = $key.'='.$value;
        }

        return implode('&', $pairs);
    }

    private function buildSubject(Order $order): string
    {
        $orderNo = trim((string) $order->order_no);
        $name = $orderNo !== '' ? $orderNo : 'Order';

        return mb_strimwidth($name, 0, 120, '', 'UTF-8');
    }

    public function publicKey(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('public_key', (string) config('payments.alipay.public_key'), $channel);
    }

    private function privateKey(?PaymentChannel $channel = null): string
    {
        return $this->channelConfigValue('private_key', (string) config('payments.alipay.private_key'), $channel);
    }

    private function publicKeyPem(?PaymentChannel $channel = null): string
    {
        return $this->normalizeKey($this->publicKey($channel), 'PUBLIC KEY');
    }

    private function privateKeyPem(?PaymentChannel $channel = null): string
    {
        return $this->normalizeKey($this->privateKey($channel), 'PRIVATE KEY');
    }

    private function normalizeKey(string $key, string $type): string
    {
        $normalized = preg_replace('/-----BEGIN [^-]+-----|-----END [^-]+-----|\s+/', '', $key) ?? '';
        $chunks = trim(chunk_split($normalized, 64, "\n"));

        return "-----BEGIN {$type}-----\n{$chunks}\n-----END {$type}-----";
    }
}
