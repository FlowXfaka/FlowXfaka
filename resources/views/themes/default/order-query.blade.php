@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrandIconPath = $siteSettings->resolvedBrandIconPath();
    $siteBrandIconUrl = $siteBrandIconPath ? asset($siteBrandIconPath) : null;
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
    $backgroundImageUrl = asset($siteSettings->resolvedBackgroundPath());
    $frontendTextMode = $siteSettings->resolvedFrontendTextMode();
    $storefrontThemeKey = $siteSettings->resolvedFrontendTheme();
    $storefrontThemeStyle = $siteSettings->resolvedStorefrontThemeStyle($backgroundImageUrl);
    $queryMode = $contact !== '' && $orderNo === '' ? 'contact' : 'order';
    $siteCssVersion = \App\Support\StorefrontTheme::assetVersion('site.css', $storefrontThemeKey);
    $siteJsVersion = \App\Support\StorefrontTheme::assetVersion('site.js', $storefrontThemeKey);
    $unlockOrderId = (string) old('order_id', '');
    $statusDelivered = "\u{5DF2}\u{53D1}\u{8D27}";
    $statusPaid = "\u{5DF2}\u{652F}\u{4ED8}";
    $pageTitle = "\u{8BA2}\u{5355}\u{67E5}\u{8BE2}";
    $pageDescription = "\u{901A}\u{8FC7}\u{8BA2}\u{5355}\u{53F7}\u{6216}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{67E5}\u{8BE2}\u{8BA2}\u{5355}\u{72B6}\u{6001}\u{3002}";
    $backHomeLabel = "\u{8FD4}\u{56DE}\u{9996}\u{9875}";
    $headingLabel = "\u{67E5}\u{8BE2}\u{8BA2}\u{5355}\u{72B6}\u{6001}";
    $subtitleLabel = "\u{652F}\u{6301}\u{901A}\u{8FC7}\u{8BA2}\u{5355}\u{53F7}\u{6216}\u{4E0B}\u{5355}\u{65F6}\u{586B}\u{5199}\u{7684}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{627E}\u{56DE}\u{8BA2}\u{5355}\u{3002}";
    $queryModeLabel = "\u{67E5}\u{8BE2}\u{65B9}\u{5F0F}";
    $orderTabLabel = "\u{8BA2}\u{5355}\u{53F7}\u{67E5}\u{8BE2}";
    $contactTabLabel = "\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{67E5}\u{8BE2}";
    $orderPlaceholder = "\u{8BF7}\u{8F93}\u{5165}\u{8BA2}\u{5355}\u{53F7}";
    $contactPlaceholder = "\u{8BF7}\u{8F93}\u{5165}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}";
    $submitLabel = "\u{7ACB}\u{5373}\u{67E5}\u{8BE2}";
    $deliveredHintLabel = "\u{5DF2}\u{5B8C}\u{6210}\u{53D1}\u{8D27}";
    $fallbackProductLabel = "\u{8BA2}\u{5355}\u{5546}\u{54C1}";
    $offlineProductLabel = "\u{5546}\u{54C1}\u{5DF2}\u{4E0B}\u{67B6}";
    $orderNoLabel = "\u{8BA2}\u{5355}\u{53F7}";
    $contactLabel = "\u{8054}\u{7CFB}\u{65B9}\u{5F0F}";
    $amountLabel = "\u{5B9E}\u{4ED8}\u{91D1}\u{989D}";
    $createdAtLabel = "\u{521B}\u{5EFA}\u{65F6}\u{95F4}";
    $unlockTitle = "\u{53D6}\u{8D27}\u{7801}\u{9A8C}\u{8BC1}";
    $unlockHint = "\u{5DF2}\u{627E}\u{5230}\u{5BF9}\u{5E94}\u{8BA2}\u{5355}\u{3002}\u{8BF7}\u{8F93}\u{5165} 6 \u{4F4D}\u{53D6}\u{8D27}\u{7801}\u{540E}\u{67E5}\u{770B}\u{5361}\u{5BC6}\u{3002}";
    $pickupCodePlaceholder = "\u{8BF7}\u{8F93}\u{5165} 6 \u{4F4D}\u{53D6}\u{8D27}\u{7801}";
    $unlockSubmitLabel = "\u{67E5}\u{770B}\u{5361}\u{5BC6}";
    $deliveryTitle = "\u{53D1}\u{8D27}\u{5361}\u{5BC6}";
    $copyLabel = "\u{4E00}\u{952E}\u{590D}\u{5236}";
    $paidHint = "\u{8BA2}\u{5355}\u{5DF2}\u{652F}\u{4ED8}\u{FF0C}\u{7CFB}\u{7EDF}\u{6B63}\u{5728}\u{81EA}\u{52A8}\u{53D1}\u{8D27}\u{3002}";
    $undeliveredHint = "\u{5F53}\u{524D}\u{8BA2}\u{5355}\u{6682}\u{672A}\u{53D1}\u{8D27}\u{3002}";
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} - {{ $siteName }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="stylesheet" href="{{ \App\Support\StorefrontTheme::assetUrl('site.css', $storefrontThemeKey) }}?v={{ $siteCssVersion }}">
    <script src="{{ \App\Support\StorefrontTheme::assetUrl('site.js', $storefrontThemeKey) }}?v={{ $siteJsVersion }}" defer></script>
</head>
<body class="theme-text-{{ $frontendTextMode }}" style="{{ $storefrontThemeStyle }}">
    <div class="page-shell">
        <header class="storefront-topbar container">
            @include(\App\Support\StorefrontTheme::view('partials.storefront-brand', $storefrontThemeKey), ['href' => route('home'), 'siteName' => $siteName, 'siteBrandIconUrl' => $siteBrandIconUrl])

            <div class="storefront-topbar__actions">
                <a class="storefront-topbar__action storefront-topbar__action--ghost" href="{{ route('home') }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M14.78 6.22 9 12l5.78 5.78-1.06 1.06L6.88 12l6.84-6.84 1.06 1.06Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $backHomeLabel }}</span>
                </a>
            </div>
        </header>

        <main class="container storefront-query-page">
            <section class="storefront-query-shell" aria-labelledby="query-heading">
                <h1 class="storefront-query-shell__title" id="query-heading">{{ $headingLabel }}</h1>
                <p class="storefront-query-shell__subtitle">{{ $subtitleLabel }}</p>

                <div class="storefront-query-tabs" data-query-switch data-mode="{{ $queryMode }}" data-query-submitted="{{ $querySubmitted ? '1' : '0' }}">
                    <div class="storefront-query-tabs__row" role="tablist" aria-label="{{ $queryModeLabel }}">
                        <button type="button" class="storefront-query-tab {{ $queryMode === 'order' ? 'is-active' : '' }}" data-query-tab="order">{{ $orderTabLabel }}</button>
                        <button type="button" class="storefront-query-tab {{ $queryMode === 'contact' ? 'is-active' : '' }}" data-query-tab="contact">{{ $contactTabLabel }}</button>
                    </div>

                    <form class="storefront-query-form" method="GET" action="{{ route('order.query') }}">
                        <input
                            class="storefront-query-form__input {{ $queryMode === 'contact' ? 'query-hidden' : '' }}"
                            type="text"
                            name="order_no"
                            value="{{ $orderNo }}"
                            placeholder="{{ $orderPlaceholder }}"
                            data-query-input="order"
                            {{ $queryMode === 'contact' ? 'disabled' : '' }}
                        >
                        <input
                            class="storefront-query-form__input {{ $queryMode === 'order' ? 'query-hidden' : '' }}"
                            type="text"
                            name="contact"
                            value="{{ $contact }}"
                            placeholder="{{ $contactPlaceholder }}"
                            data-query-input="contact"
                            {{ $queryMode === 'order' ? 'disabled' : '' }}
                        >
                        <button type="submit" class="storefront-query-form__button">{{ $submitLabel }}</button>
                    </form>
                </div>

                @if (session('order_notice'))
                    <div class="storefront-message storefront-message--success">{{ session('order_notice') }}</div>
                @endif

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
                                    $isUnlockTarget = $unlockOrderId !== '' && $unlockOrderId === (string) ($lookupOrder['unlock_order_id'] ?? '');
                                @endphp
                                <article class="storefront-query-result">
                                    <div class="storefront-query-result__head">
                                        <div class="storefront-query-result__product">
                                            <img src="{{ $orderImageUrl }}" alt="{{ $lookupOrder['product_name'] ?? $fallbackProductLabel }}">
                                            <div class="storefront-query-result__copy">
                                                @if ($lookupOrder['status'] === $statusDelivered)
                                                    <div class="storefront-query-result__status-line">
                                                        <span class="storefront-query-result__status-dot"></span>
                                                        <span>{{ $deliveredHintLabel }}</span>
                                                    </div>
                                                @endif
                                                <h2>{{ $lookupOrder['product_name'] ?? $offlineProductLabel }}</h2>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="storefront-query-result__body">
                                        <div class="storefront-query-result__meta">
                                            <div class="storefront-query-result__meta-row"><span>{{ $orderNoLabel }}</span><strong>{{ $lookupOrder['order_no'] }}</strong></div>
                                            <div class="storefront-query-result__meta-row"><span>{{ $contactLabel }}</span><strong>{{ $lookupOrder['contact'] }}</strong></div>
                                            <div class="storefront-query-result__meta-row"><span>{{ $amountLabel }}</span><strong class="storefront-query-result__amount">&#165;{{ $lookupOrder['amount'] }}</strong></div>
                                            <div class="storefront-query-result__meta-row"><span>{{ $createdAtLabel }}</span><strong>{{ $lookupOrder['created_at'] ?? '--' }}</strong></div>
                                        </div>

                                        @if ($queryMode === 'contact')
                                            <div class="storefront-query-result__delivery storefront-query-result__delivery--unlock">
                                                <div class="storefront-query-result__delivery-head">
                                                    <span>{{ $unlockTitle }}</span>
                                                </div>
                                                <div class="storefront-query-result__delivery-body storefront-query-result__delivery-body--pickup">
                                                    <p>{{ $unlockHint }}</p>
                                                </div>
                                                <form class="storefront-query-unlock-form" method="POST" action="{{ route('orders.unlock') }}">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $lookupOrder['unlock_order_id'] }}">
                                                    <input type="hidden" name="contact" value="{{ $lookupOrder['contact'] }}">
                                                    <input
                                                        class="storefront-query-unlock-form__input"
                                                        type="text"
                                                        name="pickup_code"
                                                        value="{{ $isUnlockTarget ? old('pickup_code') : '' }}"
                                                        inputmode="text"
                                                        maxlength="6"
                                                        autocomplete="off"
                                                        spellcheck="false"
                                                        placeholder="{{ $pickupCodePlaceholder }}"
                                                    >
                                                    <button type="submit" class="storefront-query-unlock-form__button">{{ $unlockSubmitLabel }}</button>
                                                </form>
                                                @if ($isUnlockTarget && $errors->has('pickup_code'))
                                                    <div class="storefront-message storefront-message--error storefront-query-result__inline-message">
                                                        {{ $errors->first('pickup_code') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="storefront-query-result__delivery">
                                                <div class="storefront-query-result__delivery-head">
                                                    <span>{{ $deliveryTitle }}</span>
                                                    @if ($deliveryContent !== '')
                                                        <button type="button" class="storefront-copy-button" data-copy-text="{{ $deliveryContent }}">{{ $copyLabel }}</button>
                                                    @endif
                                                </div>
                                                <div class="storefront-query-result__delivery-body">
                                                    @if ($deliveryContent !== '')
                                                        <pre>{{ $deliveryContent }}</pre>
                                                    @elseif ($lookupOrder['status'] === $statusPaid)
                                                        <p>{{ $paidHint }}</p>
                                                    @else
                                                        <p>{{ $undeliveredHint }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
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
