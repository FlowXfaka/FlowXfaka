<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class OrderPaymentStatusSyncThrottle
{
    private const MIN_COOLDOWN_MS = 1000;

    public function acquire(Order $order, string $providerKey): bool
    {
        $cooldownMs = $this->cooldownMs($providerKey);
        $expiresAt = now()->addMilliseconds($cooldownMs);
        $nextAllowedAtMs = $expiresAt->getTimestampMs();
        $cacheKey = $this->cacheKey($order, $providerKey);

        if (Cache::add($cacheKey, $nextAllowedAtMs, $expiresAt)) {
            return true;
        }

        if ($this->remainingMs($order, $providerKey) > 0) {
            return false;
        }

        Cache::put($cacheKey, $nextAllowedAtMs, $expiresAt);

        return true;
    }

    public function nextPollDelayMs(Order $order, string $providerKey): int
    {
        $remainingMs = $this->remainingMs($order, $providerKey);

        if ($remainingMs > 0) {
            return max(self::MIN_COOLDOWN_MS, $remainingMs);
        }

        return $this->cooldownMs($providerKey);
    }

    private function remainingMs(Order $order, string $providerKey): int
    {
        $nextAllowedAtMs = (int) Cache::get($this->cacheKey($order, $providerKey), 0);

        return $nextAllowedAtMs - now()->getTimestampMs();
    }

    private function cooldownMs(string $providerKey): int
    {
        return max(
            self::MIN_COOLDOWN_MS,
            (int) config('payments.' . $providerKey . '.status_poll_interval_ms', 5000),
        );
    }

    private function cacheKey(Order $order, string $providerKey): string
    {
        return 'payments:status-sync-throttle:' . $providerKey . ':' . $order->getKey();
    }
}
