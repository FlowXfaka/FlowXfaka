<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCard;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    private const STATUS_PENDING = "\u{5f85}\u{652f}\u{4ed8}";
    private const STATUS_DELIVERED = "\u{5df2}\u{53d1}\u{8d27}";
    private const CARD_UNUSED = "\u{672a}\u{4f7f}\u{7528}";

    public function fulfill(Order $order): string
    {
        return DB::transaction(function () use ($order): string {
            $lockedOrder = Order::query()
                ->with('product')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return 'missing';
            }

            if ($lockedOrder->status === self::STATUS_DELIVERED) {
                return 'already_fulfilled';
            }

            if (! $lockedOrder->product || ! $lockedOrder->product->is_active) {
                return 'product_missing';
            }

            $quantity = max(1, (int) $lockedOrder->quantity);
            $dispatchMode = Product::normalizeCardDispatchMode($lockedOrder->product->card_dispatch_mode ?? null);
            $cardsQuery = ProductCard::query()
                ->where('product_id', $lockedOrder->product_id)
                ->where('status', self::CARD_UNUSED)
                ->lockForUpdate();

            if ($dispatchMode === Product::CARD_DISPATCH_RANDOM) {
                $cardsQuery->inRandomOrder();
            } elseif ($dispatchMode === Product::CARD_DISPATCH_OLD_FIRST) {
                $cardsQuery->orderBy('id');
            } else {
                $cardsQuery->orderByDesc('id');
            }

            $cards = $cardsQuery
                ->limit($quantity)
                ->get();

            if ($cards->count() < $quantity) {
                return 'out_of_stock';
            }

            $deliveredCards = [];
            foreach ($cards as $card) {
                $note = trim((string) ($card->note ?? ''));
                $suffix = 'ORDER:' . $lockedOrder->order_no;

                $card->update([
                    'status' => self::STATUS_DELIVERED,
                    'note' => $note !== '' ? $note . ' | ' . $suffix : $suffix,
                ]);

                $deliveredCards[] = $card->card_value;
            }

            $lockedOrder->update([
                'status' => self::STATUS_DELIVERED,
                'paid_at' => $lockedOrder->paid_at ?? now(),
                'delivered_at' => now(),
                'delivered_cards' => $deliveredCards,
            ]);

            $lockedOrder->product->increment('sold_count', $quantity);

            return 'fulfilled';
        });
    }
}
