<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PaymentChannel extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'merchant_id',
        'merchant_public_key',
        'merchant_private_key',
        'provider_config',
        'payment_mark',
        'payment_scene',
        'payment_method',
        'route_path',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getMerchantPublicKeyAttribute($value): ?string
    {
        return $this->decryptColumn($value);
    }

    public function setMerchantPublicKeyAttribute($value): void
    {
        $this->attributes['merchant_public_key'] = $this->encryptColumn($value);
    }

    public function getMerchantPrivateKeyAttribute($value): ?string
    {
        return $this->decryptColumn($value);
    }

    public function setMerchantPrivateKeyAttribute($value): void
    {
        $this->attributes['merchant_private_key'] = $this->encryptColumn($value);
    }

    /**
     * @return array<string, string>
     */
    public function getProviderConfigAttribute($value): array
    {
        return $this->decodeProviderConfig($value);
    }

    /**
     * @param  array<string, mixed>|string|null  $value
     */
    public function setProviderConfigAttribute($value): void
    {
        $this->attributes['provider_config'] = $this->encodeProviderConfig($value);
    }

    public function sceneLabel(): string
    {
        return match ($this->payment_scene) {
            'pc' => "\u{7535}\u{8111} PC",
            'mobile' => "\u{624b}\u{673a}",
            default => "\u{901a}\u{7528}",
        };
    }

    public function methodLabel(): string
    {
        return match ($this->payment_method) {
            'scan' => "\u{626b}\u{7801}",
            default => "\u{8df3}\u{8f6c}",
        };
    }

    public function enabledLabel(): string
    {
        return $this->is_enabled ? "\u{5df2}\u{542f}\u{7528}" : "\u{5df2}\u{5173}\u{95ed}";
    }

    public function maskedPublicKey(): string
    {
        return $this->maskText($this->merchant_public_key);
    }

    public function maskedPrivateKey(): string
    {
        return $this->maskText($this->merchant_private_key);
    }

    public function configValue(string $key, string $default = ''): string
    {
        $config = $this->provider_config;
        $value = trim((string) ($config[$key] ?? ''));

        if ($value !== '') {
            return $value;
        }

        if (trim((string) $this->provider) === 'alipay') {
            return match ($key) {
                'app_id' => trim((string) ($this->merchant_id ?? '')) ?: trim($default),
                'public_key' => trim((string) ($this->merchant_public_key ?? '')) ?: trim($default),
                'private_key' => trim((string) ($this->merchant_private_key ?? '')) ?: trim($default),
                default => trim($default),
            };
        }

        return trim($default);
    }

    public function summaryValue(string $key, bool $secret = false): string
    {
        $value = $this->configValue($key);

        if ($value === '') {
            return '-';
        }

        if ($secret) {
            return "\u{5df2}\u{586b}\u{5199}";
        }

        return $this->maskText($value);
    }

    private function decryptColumn($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    private function encryptColumn($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            Crypt::decryptString($value);

            return $value;
        } catch (Throwable) {
            return Crypt::encryptString($value);
        }
    }

    /**
     * @param  array<string, mixed>|string|null  $value
     */
    private function encodeProviderConfig($value): ?string
    {
        if (is_string($value)) {
            $raw = trim($value);
            if ($raw === '') {
                return null;
            }

            try {
                $decoded = Crypt::decryptString($raw);
                $config = json_decode($decoded, true);
                if (is_array($config)) {
                    return $raw;
                }
            } catch (Throwable) {
            }

            $config = json_decode($raw, true);
            if (! is_array($config)) {
                return null;
            }

            $value = $config;
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = $this->normalizeProviderConfig($value);
        if ($normalized === []) {
            return null;
        }

        return Crypt::encryptString(json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, string>
     */
    private function decodeProviderConfig($value): array
    {
        if (is_array($value)) {
            return $this->normalizeProviderConfig($value);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        try {
            $raw = Crypt::decryptString($raw);
        } catch (Throwable) {
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $this->normalizeProviderConfig($decoded) : [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private function normalizeProviderConfig(array $config): array
    {
        $normalized = [];

        foreach ($config as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $field = trim($key);
            $stringValue = trim((string) $value);

            if ($stringValue !== '') {
                try {
                    $stringValue = trim((string) Crypt::decryptString($stringValue));
                } catch (Throwable) {
                }
            }

            if ($field === '' || $stringValue === '') {
                continue;
            }

            $normalized[$field] = $stringValue;
        }

        return $normalized;
    }

    private function maskText(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        if (mb_strlen($value, 'UTF-8') <= 24) {
            return $value;
        }

        return mb_substr($value, 0, 18, 'UTF-8') . '...' . mb_substr($value, -8, null, 'UTF-8');
    }
}
