@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrandIconPath = $siteSettings->resolvedBrandIconPath();
    $siteBrandIconUrl = $siteBrandIconPath ? asset($siteBrandIconPath) : null;
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
    $backgroundImageUrl = asset($siteSettings->resolvedBackgroundPath());
    $frontendTextMode = $siteSettings->resolvedFrontendTextMode();
    $storefrontThemeStyle = $siteSettings->resolvedStorefrontThemeStyle($backgroundImageUrl);
    $queryMode = $contact !== '' && $orderNo === '' ? 'contact' : 'order';
    $siteCssVersion = @filemtime(public_path('site.css')) ?: time();
    $siteJsVersion = @filemtime(public_path('site.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>订单查询 - {{ $siteName }}</title>
    <meta name="description" content="订单查询页面。">
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="stylesheet" href="{{ asset('site.css') }}?v={{ $siteCssVersion }}">
    <script src="{{ asset('site.js') }}?v={{ $siteJsVersion }}" defer></script>
</head>
<body class="theme-text-{{ $frontendTextMode }}" style="{{ $storefrontThemeStyle }}">
    <div class="page-shell">
        <header class="storefront-topbar container">
            @include('partials.storefront-brand', ['href' => route('home'), 'siteName' => $siteName, 'siteBrandIconUrl' => $siteBrandIconUrl])

            <div class="storefront-topbar__actions">
                <a class="storefront-topbar__action storefront-topbar__action--ghost" href="{{ route('home') }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M14.78 6.22 9 12l5.78 5.78-1.06 1.06L6.88 12l6.84-6.84 1.06 1.06Z" fill="currentColor"/>
                    </svg>
                    <span>返回</span>
                </a>
            </div>
        </header>

        <main class="container storefront-query-page">
            <section class="storefront-query-shell" aria-labelledby="query-heading">
                <h1 class="storefront-query-shell__title" id="query-heading">查询订单状态</h1>
                <p class="storefront-query-shell__subtitle">支持通过订单号或下单留存的联系方式找回您的服务凭证</p>

                <div class="storefront-query-tabs" data-query-switch data-mode="{{ $queryMode }}" data-query-submitted="{{ $querySubmitted ? '1' : '0' }}">
                    <div class="storefront-query-tabs__row" role="tablist" aria-label="查询方式">
                        <button type="button" class="storefront-query-tab {{ $queryMode === 'order' ? 'is-active' : '' }}" data-query-tab="order">订单号查询</button>
                        <button type="button" class="storefront-query-tab {{ $queryMode === 'contact' ? 'is-active' : '' }}" data-query-tab="contact">联系方式查询</button>
                    </div>

                    <form class="storefront-query-form" method="GET" action="{{ route('order.query') }}">
                        <input
                            class="storefront-query-form__input {{ $queryMode === 'contact' ? 'query-hidden' : '' }}"
                            type="text"
                            name="order_no"
                            value="{{ $orderNo }}"
                            placeholder="请输入订单号"
                            data-query-input="order"
                            {{ $queryMode === 'contact' ? 'disabled' : '' }}
                        >
                        <input
                            class="storefront-query-form__input {{ $queryMode === 'order' ? 'query-hidden' : '' }}"
                            type="text"
                            name="contact"
                            value="{{ $contact }}"
                            placeholder="请输入联系方式"
                            data-query-input="contact"
                            {{ $queryMode === 'order' ? 'disabled' : '' }}
                        >
                        <button type="submit" class="storefront-query-form__button">立即查询</button>
                    </form>
                </div>

                @if ($querySubmitted)
                    <div class="storefront-query-results">
                        @if ($lookupError)
                            <div class="storefront-message storefront-message--error">{{ $lookupError }}</div>
                        @elseif (! empty($lookupOrders))
                            @foreach ($lookupOrders as $lookupOrder)
                                @php
                                    $orderImage = $lookupOrder['product_image'];
                                    $orderImageUrl = str_starts_with($orderImage, 'http://') || str_starts_with($orderImage, 'https://') ? $orderImage : asset($orderImage);
                                    $deliveryContent = collect($lookupOrder['delivered_cards'] ?? [])->filter()->implode("\n");
                                @endphp
                                <article class="storefront-query-result">
                                    <div class="storefront-query-result__head">
                                        <div class="storefront-query-result__product">
                                            <img src="{{ $orderImageUrl }}" alt="{{ $lookupOrder['product_name'] ?? '订单商品' }}">
                                            <div class="storefront-query-result__copy">
                                                @if ($lookupOrder['status'] === '已发货')
                                                    <div class="storefront-query-result__status-line">
                                                        <span class="storefront-query-result__status-dot"></span>
                                                        <span>已完成发货</span>
                                                    </div>
                                                @endif
                                                <h2>{{ $lookupOrder['product_name'] ?? '商品已下架' }}</h2>
                                            </div>
                                        </div>
                                    </div>

                                        <div class="storefront-query-result__body">
                                            <div class="storefront-query-result__meta">
                                                <div class="storefront-query-result__meta-row"><span>订单号</span><strong>{{ $lookupOrder['order_no'] }}</strong></div>
                                                <div class="storefront-query-result__meta-row"><span>联系方式</span><strong>{{ $lookupOrder['contact'] }}</strong></div>
                                                <div class="storefront-query-result__meta-row"><span>实付金额</span><strong class="storefront-query-result__amount">¥{{ $lookupOrder['amount'] }}</strong></div>
                                                <div class="storefront-query-result__meta-row"><span>创建时间</span><strong>{{ $lookupOrder['created_at'] ?? '--' }}</strong></div>
                                            </div>

                                        <div class="storefront-query-result__delivery">
                                            <div class="storefront-query-result__delivery-head">
                                                <span>发货卡密</span>
                                                @if ($deliveryContent !== '')
                                                    <button type="button" class="storefront-copy-button" data-copy-text="{{ $deliveryContent }}">一键复制</button>
                                                @endif
                                            </div>
                                            <div class="storefront-query-result__delivery-body">
                                                @if ($deliveryContent !== '')
                                                    <pre>{{ $deliveryContent }}</pre>
                                                @else
                                                    <p>{{ $lookupOrder['status'] === '已支付' ? '订单已支付，系统正在自动发货。' : '当前订单暂未发货。' }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </article>
                            @endforeach
                        @endif
                    </div>
                @endif
            </section>
        </main>
    </div>
</body>
</html>
