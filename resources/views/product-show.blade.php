@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrandIconPath = $siteSettings->resolvedBrandIconPath();
    $siteBrandIconUrl = $siteBrandIconPath ? asset($siteBrandIconPath) : null;
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
    $backgroundImageUrl = asset($siteSettings->resolvedBackgroundPath());
    $frontendTextMode = $siteSettings->resolvedFrontendTextMode();
    $storefrontThemeStyle = $siteSettings->resolvedStorefrontThemeStyle($backgroundImageUrl);
    $image = $productData['image'];
    $imageUrl = str_starts_with($image, 'http://') || str_starts_with($image, 'https://') ? $image : asset($image);
    $quantityMax = max(1, (int) $maxQuantity);
    $initialQuantity = max(1, min($quantityMax, (int) old('quantity', 1)));
    $paymentChannels = $paymentChannels ?? [];
    $selectedPaymentChannel = (string) old('payment_channel', (string) ($paymentChannels[0]['id'] ?? ''));
    $canCheckout = $paymentChannels !== [] && $productData['stock'] > 0 && (float) $productData['price'] >= 0.01;
    $siteCssVersion = @filemtime(public_path('site.css')) ?: time();
    $siteJsVersion = @filemtime(public_path('site.js')) ?: time();
    $submitLabel = $paymentChannels === []
        ? '暂无可用支付方式'
        : ($productData['stock'] < 1
            ? '暂时缺货'
            : ((float) $productData['price'] < 0.01 ? '商品暂不可支付' : '立即支付'));
    $comparePrice = $productData['compare_price'] ?? null;
    $detailTagStyle = in_array((string) ($productData['detail_tag_style'] ?? ''), ['glass', 'minimal', 'gradient'], true)
        ? (string) $productData['detail_tag_style']
        : 'glass';
    $detailTags = is_array($productData['detail_tags'] ?? null) ? $productData['detail_tags'] : [];
    $detailStockTone = in_array((string) ($productData['stock_tone'] ?? ''), ['plenty', 'normal', 'tight', 'low', 'out'], true)
        ? (string) $productData['stock_tone']
        : 'normal';
    $detailStockText = (string) ($productData['stock_label'] ?? '暂时缺货');
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $productData['name'] }} - {{ $siteName }}</title>
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <meta name="description" content="商品详情与支付页面">
    <link rel="stylesheet" href="{{ asset('site.css') }}?v={{ $siteCssVersion }}">
    <script src="{{ asset('site.js') }}?v={{ $siteJsVersion }}" defer></script>
</head>
<body class="theme-text-{{ $frontendTextMode }}" style="{{ $storefrontThemeStyle }}">
    <div class="page-shell">
        <header class="storefront-topbar container">
            @include('partials.storefront-brand', ['href' => route('home'), 'siteName' => $siteName, 'siteBrandIconUrl' => $siteBrandIconUrl])

            <div class="storefront-topbar__actions">
                <a class="storefront-topbar__action storefront-topbar__action--ghost" href="{{ $productData['category_slug'] ? route('home', ['category' => $productData['category_slug']]) : route('home') }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M14.78 6.22 9 12l5.78 5.78-1.06 1.06L6.88 12l6.84-6.84 1.06 1.06Z" fill="currentColor"/>
                    </svg>
                    <span>返回</span>
                </a>
                <a class="storefront-topbar__action" href="{{ route('order.query') }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M6 3.5A2.5 2.5 0 0 0 3.5 6v12A2.5 2.5 0 0 0 6 20.5h12a2.5 2.5 0 0 0 2.5-2.5V9.56a2.5 2.5 0 0 0-.73-1.77l-3.56-3.56A2.5 2.5 0 0 0 14.44 3.5H6Zm0 1.5h8v3.5A1.5 1.5 0 0 0 15.5 10H19v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9.5.56L17.94 8H15.5a.5.5 0 0 1-.5-.5V5.06Z" fill="currentColor"/>
                    </svg>
                    <span>订单查询</span>
                </a>
            </div>
        </header>

        <main class="container storefront-detail-page">
            <div class="storefront-detail-stack">
                <div class="storefront-detail-layout">
                    <section class="storefront-detail-panel storefront-detail-panel--summary">
                        <div class="storefront-detail-panel__left">
                            <div class="storefront-detail-panel__media">
                                <img src="{{ $imageUrl }}" alt="{{ $productData['name'] }}">
                            </div>
                            <span class="storefront-detail-panel__media-widget storefront-detail-panel__media-widget--{{ $detailStockTone }}">
                                {{ $detailStockText }}
                            </span>
                        </div>
                        <div class="storefront-detail-panel__summary">
                            <div class="storefront-detail-panel__title-wrap">
                                <h1 class="storefront-detail-panel__title">{{ $productData['name'] }}</h1>
                            </div>

                            @if ($detailTags !== [])
                                <div class="storefront-detail-panel__tags">
                                    @foreach ($detailTags as $tag)
                                        <span class="storefront-detail-chip storefront-detail-chip--{{ $detailTagStyle }} storefront-detail-chip--{{ $tag['tone'] ?? 'blue' }}">
                                            <span class="storefront-detail-chip__icon">{{ $tag['icon'] ?? '' }}</span>
                                            <span>{{ $tag['text'] ?? '' }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="storefront-detail-panel__footer">
                                <div class="storefront-detail-panel__amount">
                                    <span>¥</span>
                                    <strong>{{ $productData['price'] }}</strong>
                                    @if ($comparePrice !== null && trim((string) $comparePrice) !== '')
                                        <em>¥{{ $comparePrice }}</em>
                                    @endif
                                </div>
                                <div class="storefront-detail-panel__footer-widget">
                                    <button type="button" class="storefront-detail-panel__share" data-product-share>
                                        <svg class="storefront-detail-panel__share-icon" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                            <path d="M15 8a3 3 0 1 0-2.82-4H12a3 3 0 0 0 .18 1.01L8.7 7.06a3 3 0 0 0-1.7-.56 3 3 0 1 0 1.7 5.44l3.48 2.05A3 3 0 0 0 12 15a3 3 0 1 0 .18 1.01L8.7 18.06a3 3 0 1 0 .76 1.3l3.48-2.05A3 3 0 1 0 15 8Z"/>
                                        </svg>
                                        <span data-share-label>分享给好友</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="storefront-detail-panel storefront-detail-panel--checkout">
                        @if ($errors->any())
                            <div class="storefront-message storefront-message--error">{{ $errors->first() }}</div>
                        @endif

                        <form class="storefront-checkout-form" method="POST" action="{{ route('orders.store', ['product' => $productData['sku']]) }}" data-no-spa>
                            @csrf

                            <div class="storefront-detail-field">
                                <label class="storefront-detail-field__label" for="contact">联系方式</label>
                                <input id="contact" class="storefront-detail-field__input" type="text" name="contact" value="{{ old('contact') }}" maxlength="120" placeholder="微信号/QQ/手机号" required autocomplete="off" oninvalid="this.setCustomValidity('请填写联系方式')" oninput="this.setCustomValidity('')">
                            </div>

                            <div class="storefront-detail-field">
                                <label class="storefront-detail-field__label">下单数量</label>
                                <div class="storefront-detail-quantity checkout-quantity-row" data-unit-price="{{ $productData['price'] }}">
                                    <div class="storefront-detail-quantity__total">
                                        <span>总计金额</span>
                                        <strong data-order-total>¥{{ number_format((float) $productData['price'] * $initialQuantity, 2, '.', '') }}</strong>
                                    </div>
                                    <div class="storefront-quantity-stepper quantity-stepper storefront-detail-quantity__stepper" data-quantity-stepper data-max="{{ $quantityMax }}">
                                            <button type="button" class="quantity-stepper__button" data-action="decrease" onclick="window.FlowXAdjustQuantity && window.FlowXAdjustQuantity(this,-1)" aria-label="减少数量">
                                            <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                                <path d="M5 11.25h14v1.5H5z" fill="currentColor"/>
                                            </svg>
                                        </button>
                                        <input
                                            class="quantity-stepper__input"
                                            type="number"
                                            name="quantity"
                                            min="1"
                                            max="{{ $quantityMax }}"
                                            value="{{ $initialQuantity }}"
                                            inputmode="numeric"
                                                    oninput="window.FlowXSyncOrderTotal && window.FlowXSyncOrderTotal(this)"
                                                    onchange="window.FlowXSyncOrderTotal && window.FlowXSyncOrderTotal(this)"
                                        >
                                            <button type="button" class="quantity-stepper__button" data-action="increase" onclick="window.FlowXAdjustQuantity && window.FlowXAdjustQuantity(this,1)" aria-label="增加数量">
                                            <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                                <path d="M11.25 5h1.5v6.25H19v1.5h-6.25V19h-1.5v-6.25H5v-1.5h6.25z" fill="currentColor"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="storefront-detail-field">
                                <label class="storefront-detail-field__label">支付方式</label>
                                <div class="storefront-payment-options checkout-payment-options storefront-detail-field__payments">
                                    @foreach ($paymentChannels as $channel)
                                        <label class="storefront-payment-option checkout-payment-option {{ $selectedPaymentChannel === (string) $channel['id'] ? 'is-selected' : '' }}">
                                            <input type="radio" name="payment_channel" value="{{ $channel['id'] }}" {{ $selectedPaymentChannel === (string) $channel['id'] ? 'checked' : '' }}>
                                            <span class="storefront-payment-option__icon">
                                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                                    <path d="M3.5 6A2.5 2.5 0 0 1 6 3.5h12A2.5 2.5 0 0 1 20.5 6v1.5h-17V6Zm17 3h-17V18A2.5 2.5 0 0 0 6 20.5h12a2.5 2.5 0 0 0 2.5-2.5V9ZM7 14.25h4v1.5H7v-1.5Z" fill="currentColor"/>
                                                </svg>
                                            </span>
                                            <span>{{ $channel['name'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <button type="submit" class="storefront-pay-button storefront-detail-submit buy-submit" {{ $canCheckout ? '' : 'disabled' }}>
                                <span class="storefront-pay-button__content">
                                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path d="M12.6 2.65a.75.75 0 0 0-1.22.15L7.2 11h-2.95a.75.75 0 0 0-.56 1.25l7.15 8.15a.75.75 0 0 0 1.28-.17l4.15-8.23h3.48a.75.75 0 0 0 .56-1.26l-7.7-8.09Z" fill="currentColor"/>
                                    </svg>
                                    <span>{{ $submitLabel }}</span>
                                </span>
                            </button>
                        </form>
                    </section>
                </div>

                <section class="storefront-detail-panel storefront-detail-panel--description">
                    <div class="storefront-detail-description__head">
                        <div class="storefront-detail-description__bar"></div>
                        <h2>商品说明</h2>
                    </div>
                    <div class="storefront-detail-description__body notice-content">{!! $productData['description_html'] ?: '<p>暂无商品说明。</p>' !!}</div>
                </section>
            </div>
        </main>
    </div>
    <div class="storefront-copy-toast" role="status" aria-live="polite" data-copy-toast>已复制该商品链接</div>
</body>
</html>
