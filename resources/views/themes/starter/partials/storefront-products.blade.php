@if (empty($products))
    <div class="storefront-empty-state">
        <p>暂无匹配商品</p>
    </div>
@else
    <div class="storefront-product-grid">
        @foreach ($products as $product)
            @php
                $image = $product['image'];
                $imageUrl = str_starts_with($image, 'http://') || str_starts_with($image, 'https://') ? $image : asset($image);
                $comparePrice = $product['compare_price'] ?? null;
                $stockTone = in_array((string) ($product['stock_tone'] ?? ''), ['plenty', 'normal', 'tight', 'low', 'out'], true)
                    ? (string) $product['stock_tone']
                    : 'normal';
                $searchText = mb_strtolower(trim(($product['name'] ?? '') . ' ' . ($product['category_name'] ?? '')));
            @endphp
            <article class="storefront-product-card" data-product-card data-product-search="{{ $searchText }}">
                <a class="storefront-product-card__media" href="{{ route('products.show', ['product' => $product['sku']]) }}">
                    <img src="{{ $imageUrl }}" alt="{{ $product['name'] }}">
                    <span class="storefront-product-card__stock storefront-product-card__stock--{{ $stockTone }}">{{ $product['stock_label'] }}</span>
                </a>
                <div class="storefront-product-card__body">
                    <div class="storefront-product-card__copy">
                        <h3 class="storefront-product-card__title">
                            <a href="{{ route('products.show', ['product' => $product['sku']]) }}">{{ $product['name'] }}</a>
                        </h3>
                    </div>
                    <div class="storefront-product-card__footer">
                        <div class="storefront-product-card__price">
                            <span class="storefront-product-card__currency">¥</span>
                            <strong>{{ $product['price'] }}</strong>
                            @if ($comparePrice !== null && trim((string) $comparePrice) !== '')
                                <span class="storefront-product-card__compare">¥{{ $comparePrice }}</span>
                            @endif
                        </div>
                        <a class="storefront-product-card__button" href="{{ route('products.show', ['product' => $product['sku']]) }}">
                            <span class="storefront-product-card__button-icon">⚡</span>
                            <span>{{ (int) ($product['stock'] ?? 0) <= 0 ? '查看详情' : '立即抢购' }}</span>
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
