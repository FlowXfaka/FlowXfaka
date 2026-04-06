@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrandIconPath = $siteSettings->resolvedBrandIconPath();
    $siteBrandIconUrl = $siteBrandIconPath ? asset($siteBrandIconPath) : null;
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
    $backgroundImageUrl = asset($siteSettings->resolvedBackgroundPath());
    $frontendTextMode = $siteSettings->resolvedFrontendTextMode();
    $storefrontThemeStyle = $siteSettings->resolvedStorefrontThemeStyle($backgroundImageUrl);
    $siteNoticeHtml = $siteSettings->resolvedNoticeHtml();
    $siteCssVersion = @filemtime(public_path('site.css')) ?: time();
    $siteJsVersion = @filemtime(public_path('site.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }}</title>
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <meta name="description" content="虚拟商品首页，支持分类浏览、搜索、购买和订单查询。">
    <link rel="stylesheet" href="{{ asset('site.css') }}?v={{ $siteCssVersion }}">
    <script src="{{ asset('site.js') }}?v={{ $siteJsVersion }}" defer></script>
</head>
<body class="theme-text-{{ $frontendTextMode }}" style="{{ $storefrontThemeStyle }}">
    <div class="page-shell">
        <header class="storefront-topbar storefront-topbar--searchable container">
            @include('partials.storefront-brand', ['href' => route('home'), 'siteName' => $siteName, 'siteBrandIconUrl' => $siteBrandIconUrl])

            <label class="storefront-search" aria-label="搜索商品">
                <span class="storefront-search__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img">
                        <path d="M10.5 4a6.5 6.5 0 1 0 4.03 11.61l4.43 4.43 1.06-1.06-4.43-4.43A6.5 6.5 0 0 0 10.5 4Zm0 1.5a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z" fill="currentColor"/>
                    </svg>
                </span>
                <input
                    type="text"
                    placeholder="搜索商品"
                    data-storefront-search
                    autocomplete="off"
                >
                <button type="button" class="storefront-search__clear" data-storefront-search-clear aria-label="清除搜索" hidden>
                    <svg viewBox="0 0 24 24" role="img">
                        <path d="M7.28 6.22 6.22 7.28 10.94 12l-4.72 4.72 1.06 1.06L12 13.06l4.72 4.72 1.06-1.06L13.06 12l4.72-4.72-1.06-1.06L12 10.94 7.28 6.22Z" fill="currentColor"/>
                    </svg>
                </button>
            </label>

            <div class="storefront-topbar__actions">
                <a class="storefront-topbar__action" href="{{ route('order.query') }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M6 3.5A2.5 2.5 0 0 0 3.5 6v12A2.5 2.5 0 0 0 6 20.5h12a2.5 2.5 0 0 0 2.5-2.5V9.56a2.5 2.5 0 0 0-.73-1.77l-3.56-3.56A2.5 2.5 0 0 0 14.44 3.5H6Zm0 1.5h8v3.5A1.5 1.5 0 0 0 15.5 10H19v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9.5.56L17.94 8H15.5a.5.5 0 0 1-.5-.5V5.06Z" fill="currentColor"/>
                    </svg>
                    <span>订单查询</span>
                </a>
            </div>
        </header>

        <main class="container storefront-main">
            <section class="storefront-panel storefront-notice-panel" aria-labelledby="notice-heading">
                <h1 id="notice-heading" class="storefront-notice-panel__title">公告</h1>
                <div class="storefront-notice-panel__content">
                    <div class="notice-content">{!! $siteNoticeHtml !!}</div>
                </div>
            </section>

            <section class="storefront-panel storefront-category-panel" data-category-section>
                <div class="storefront-category-panel__row" data-category-grid>
                    @foreach ($categories as $category)
                        <a
                            href="{{ $category['slug'] !== '' ? route('home', ['category' => $category['slug']]) : route('home', ['category' => 'all']) }}"
                            class="storefront-category-pill{{ $category['is_active'] ? ' is-active' : '' }}"
                            data-category-slug="{{ $category['slug'] }}"
                        >
                            <span>{{ $category['name'] }}</span>
                            <em>{{ $category['product_count'] }}</em>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="storefront-panel storefront-product-panel" data-product-section>
                <div class="catalog-products-stack" data-product-body>
                    @foreach ($categories as $category)
                        <section class="catalog-products-panel{{ $category['is_active'] ? ' is-active' : '' }}" data-category-panel="{{ $category['slug'] }}">
                            @include('partials.storefront-products', ['products' => $category['products']])
                        </section>
                    @endforeach

                    @if (empty($categories))
                        <section class="catalog-products-panel is-active">
                            @include('partials.storefront-products', ['products' => $emptyProducts])
                        </section>
                    @endif
                </div>

                <div class="storefront-search-empty" data-storefront-search-empty hidden>
                    <p>暂无匹配商品</p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
