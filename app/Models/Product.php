<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const CARD_DISPATCH_NEW_FIRST = 'new_first';
    public const CARD_DISPATCH_OLD_FIRST = 'old_first';
    public const CARD_DISPATCH_RANDOM = 'random';

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'price',
        'compare_price',
        'stock',
        'sold_count',
        'card_dispatch_mode',
        'status',
        'sort_order',
        'image_path',
        'description_html',
        'detail_tag_style',
        'detail_tags',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock' => 'integer',
        'sold_count' => 'integer',
        'sort_order' => 'integer',
        'detail_tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(ProductCard::class, 'product_id');
    }

    public static function cardDispatchModes(): array
    {
        return [
            self::CARD_DISPATCH_NEW_FIRST,
            self::CARD_DISPATCH_OLD_FIRST,
            self::CARD_DISPATCH_RANDOM,
        ];
    }

    public static function cardDispatchModeOptions(): array
    {
        return [
            self::CARD_DISPATCH_NEW_FIRST => '先卖新卡密',
            self::CARD_DISPATCH_OLD_FIRST => '先卖旧卡密',
            self::CARD_DISPATCH_RANDOM => '新旧随机卖',
        ];
    }

    public static function normalizeCardDispatchMode(mixed $value): string
    {
        $mode = trim((string) $value);

        return in_array($mode, self::cardDispatchModes(), true)
            ? $mode
            : self::CARD_DISPATCH_NEW_FIRST;
    }
}
