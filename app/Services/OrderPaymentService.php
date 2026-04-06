<?php

namespace App\Services;

use App\Jobs\FulfillPaidOrder;
use App\Models\Order;
use Throwable;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public const STATUS_PENDING = "\u{5f85}\u{652f}\u{4ed8}";
    public const STATUS_PAID = "\u{5df2}\u{652f}\u{4ed8}";
    public const STATUS_DELIVERED = "\u{5df2}\u{53d1}\u{8d27}";

    public function __construct(
        private readonly OrderFulfillmentService $fulfillment,
    ) {
    }

    public function markManualPaid(Order|int $order, string $source = 'admin'): string
    {
        $orderId = $order instanceof Order ? (int) $order->getKey() : (int) $order;

        return DB::transaction(function () use ($orderId, $source): string {
            $lockedOrder = Order::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return 'missing';
            }

            if ($lockedOrder->status === self::STATUS_DELIVERED) {
                return 'already_delivered';
            }

            $paymentPayload = is_array($lockedOrder->payment_payload) ? $lockedOrder->payment_payload : [];
            $paymentPayload[$source] = [
                'marked_as_paid' => true,
                'marked_at' => now('Asia/Shanghai')->format('Y-m-d H:i:s'),
            ];
            $paymentPayload['last_payment_source'] = $source;
            $paymentPayload['last_payment_sync_at'] = now('Asia/Shanghai')->format('Y-m-d H:i:s');

            $lockedOrder->payment_channel = trim((string) $lockedOrder->payment_channel) !== ''
                ? (string) $lockedOrder->payment_channel
                : 'manual';
            $lockedOrder->payment_payload = $paymentPayload;
            $lockedOrder->paid_at = $lockedOrder->paid_at ?? now();

            if ($lockedOrder->status === self::STATUS_PENDING) {
                $lockedOrder->status = self::STATUS_PAID;
            }

            $lockedOrder->save();

            return 'paid';
        });
    }

    public function markAlipayPaid(Order|int $order, array $payload, string $source): string
    {
        return $this->markProviderPaid(
            $order,
            $payload,
            $source,
            'alipay',
            tradeNoKey: 'trade_no',
            buyerLogonKey: 'buyer_logon_id',
        );
    }

    public function markProviderPaid(
        Order|int $order,
        array $payload,
        string $source,
        string $provider,
        string $tradeNoKey = 'trade_no',
        ?string $buyerLogonKey = null,
    ): string
    {
        $orderId = $order instanceof Order ? (int) $order->getKey() : (int) $order;
        $providerKey = trim($provider) !== '' ? trim($provider) : 'manual';
        $paymentResult = DB::transaction(function () use ($orderId, $payload, $source, $providerKey, $tradeNoKey, $buyerLogonKey): array {
            $lockedOrder = Order::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return [
                    'status' => 'missing',
                    'should_fulfill' => false,
                    'order_id' => null,
                ];
            }

            $tradeNo = trim((string) ($payload[$tradeNoKey] ?? ''));
            $buyerLogonId = $buyerLogonKey !== null
                ? trim((string) ($payload[$buyerLogonKey] ?? ''))
                : '';
            $paymentPayload = is_array($lockedOrder->payment_payload) ? $lockedOrder->payment_payload : [];

            $paymentPayload[$source] = $payload;
            $paymentPayload['last_payment_source'] = $source;
            $paymentPayload['last_payment_sync_at'] = now('Asia/Shanghai')->format('Y-m-d H:i:s');

            $lockedOrder->payment_channel = $providerKey;
            if ($tradeNo !== '') {
                $lockedOrder->payment_trade_no = $tradeNo;
            }
            if ($buyerLogonId !== '') {
                $lockedOrder->payment_buyer_logon_id = $buyerLogonId;
            }

            $lockedOrder->payment_payload = $paymentPayload;
            $lockedOrder->payment_notified_at = $source === 'notify'
                ? now()
                : ($lockedOrder->payment_notified_at ?? now());
            $lockedOrder->paid_at = $lockedOrder->paid_at ?? now();

            if ($lockedOrder->status === self::STATUS_PENDING) {
                $lockedOrder->status = self::STATUS_PAID;
            }

            $lockedOrder->save();

            return [
                'status' => $lockedOrder->status === self::STATUS_DELIVERED
                    ? 'already_delivered'
                    : 'paid',
                'should_fulfill' => $lockedOrder->status === self::STATUS_PAID,
                'order_id' => (int) $lockedOrder->getKey(),
            ];
        });

        if (($paymentResult['should_fulfill'] ?? false) && (int) ($paymentResult['order_id'] ?? 0) > 0) {
            $this->fulfillPaidOrder((int) $paymentResult['order_id']);
        }

        return (string) ($paymentResult['status'] ?? 'missing');
    }

    private function fulfillPaidOrder(int $orderId): void
    {
        $order = Order::query()->find($orderId);

        if (! $order) {
            return;
        }

        try {
            $this->fulfillment->fulfill($order);
        } catch (Throwable $exception) {
            report($exception);
            FulfillPaidOrder::dispatch($orderId);
        }
    }
}
