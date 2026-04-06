<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Order extends Model
{
    private const PICKUP_CODE_LENGTH = 6;

    protected $fillable = [
        'order_no',
        'product_id',
        'contact',
        'pickup_code_hash',
        'pickup_code_encrypted',
        'quantity',
        'amount',
        'status',
        'payment_channel',
        'payment_trade_no',
        'payment_buyer_logon_id',
        'payment_notified_at',
        'payment_payload',
        'delivered_cards',
        'paid_at',
        'delivered_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'payment_payload' => 'array',
        'delivered_cards' => 'array',
        'payment_notified_at' => 'datetime',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function pickupCodeForAdmin(): ?string
    {
        $encryptedValue = trim((string) ($this->pickup_code_encrypted ?? ''));

        if ($encryptedValue === '') {
            return null;
        }

        try {
            $pickupCode = strtoupper((string) Crypt::decryptString($encryptedValue));
        } catch (\Throwable) {
            return null;
        }

        if (! preg_match('/^[A-Z0-9]{' . self::PICKUP_CODE_LENGTH . '}$/', $pickupCode)) {
            return null;
        }

        return $pickupCode;
    }
}
