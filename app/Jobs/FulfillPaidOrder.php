<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderFulfillmentService;
use App\Services\OrderPaymentService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class FulfillPaidOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;
    public int $uniqueFor = 120;

    public function __construct(public int $orderId)
    {
        $this->onQueue('fulfillment');
    }

    public function uniqueId(): string
    {
        return 'order-fulfillment:' . $this->orderId;
    }

    public function handle(OrderFulfillmentService $fulfillment): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order || ! in_array($order->status, [OrderPaymentService::STATUS_PAID, OrderPaymentService::STATUS_DELIVERED], true)) {
            return;
        }

        $result = $fulfillment->fulfill($order);

        if (in_array($result, ['fulfilled', 'already_fulfilled', 'missing'], true)) {
            return;
        }

        report(new RuntimeException('Unexpected fulfillment result [' . $result . '] for order #' . $this->orderId . '.'));
    }
}
