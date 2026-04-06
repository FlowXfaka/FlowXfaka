<?php

namespace App\Jobs;

use App\Models\Order;
use App\Payments\PaymentProviderRegistry;
use App\Services\OrderPaymentService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncOrderPaymentStatus implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;
    public int $uniqueFor = 8;

    public function __construct(public int $orderId)
    {
        $this->onQueue('payments');
    }

    public function uniqueId(): string
    {
        return 'order-payment-sync:' . $this->orderId;
    }

    public function handle(PaymentProviderRegistry $providers): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order || $order->status !== OrderPaymentService::STATUS_PENDING) {
            return;
        }

        $provider = $providers->forOrder($order);
        if (! $provider || ! $provider->supportsStatusSync()) {
            return;
        }

        $provider->syncPendingOrder($order);
    }
}
