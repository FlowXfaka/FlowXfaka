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
    $image = $orderData['product_image'];
    $imageUrl = str_starts_with($image, 'http://') || str_starts_with($image, 'https://') ? $image : asset($image);
    $pendingStatus = "\u{5F85}\u{652F}\u{4ED8}";
    $paidStatus = "\u{5DF2}\u{652F}\u{4ED8}";
    $deliveredStatus = "\u{5DF2}\u{53D1}\u{8D27}";
    $paymentQrCode = trim((string) ($orderData['payment_qr_code'] ?? ''));
    $siteCssVersion = \App\Support\StorefrontTheme::assetVersion('site.css', $storefrontThemeKey);
    $siteJsVersion = \App\Support\StorefrontTheme::assetVersion('site.js', $storefrontThemeKey);
    $isPaymentState = $orderData['delivered_cards'] === []
        && ! in_array($orderData['status'], [$paidStatus, $deliveredStatus], true)
        && ($paymentEnabled || $paymentQrCode !== '' || ! empty($paymentUrl));
    $userAgent = strtolower((string) request()->userAgent());
    $isMobileClient = str_contains($userAgent, 'iphone')
        || str_contains($userAgent, 'android')
        || str_contains($userAgent, 'mobile')
        || str_contains($userAgent, 'ipad')
        || str_contains($userAgent, 'ipod');
    $expiresAtMs = $expiresAtMs ?? now('Asia/Shanghai')->addMinutes(3)->getTimestampMs();
    $remainingPaymentMs = max(0, $expiresAtMs - now('Asia/Shanghai')->getTimestampMs());
    $remainingPaymentMinutes = intdiv($remainingPaymentMs, 60000);
    $remainingPaymentSeconds = intdiv($remainingPaymentMs % 60000, 1000);
    $remainingPaymentMilliseconds = $remainingPaymentMs % 1000;
    $deliveryContent = collect($orderData['delivered_cards'] ?? [])->filter()->implode("\n");
    $pickupCodeDisplay = (string) ($pickupCodeDisplay ?? '');
    $pageTitle = "\u{8BA2}\u{5355}\u{8BE6}\u{60C5}";
    $queryLabel = "\u{8BA2}\u{5355}\u{67E5}\u{8BE2}";
    $buyAgainLabel = "\u{7EE7}\u{7EED}\u{8D2D}\u{4E70}";
    $qrLoadingLabel = "\u{6B63}\u{5728}\u{751F}\u{6210}\u{652F}\u{4ED8}\u{4E8C}\u{7EF4}\u{7801}...";
    $orderIdLabel = "\u{8BA2}\u{5355}\u{7F16}\u{53F7} (ORDER ID)";
    $paidAmountLabel = "\u{652F}\u{4ED8}\u{91D1}\u{989D}";
    $countdownLabel = "\u{5269}\u{4F59}\u{652F}\u{4ED8}\u{65F6}\u{95F4}";
    $openPaymentLabel = "\u{70B9}\u{6211}\u{6253}\u{5F00}\u{652F}\u{4ED8}\u{9875}";
    $productNameLabel = "\u{5546}\u{54C1}\u{540D}\u{79F0}";
    $contactLabel = "\u{4E0B}\u{5355}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}";
    $quantityLabel = "\u{4E0B}\u{5355}\u{6570}\u{91CF}";
    $quantityUnitLabel = "\u{4EF6}";
    $amountLabel = "\u{5B9E}\u{4ED8}\u{91D1}\u{989D}";
    $createdAtLabel = "\u{521B}\u{5EFA}\u{65F6}\u{95F4}";
    $fallbackProductLabel = "\u{8BA2}\u{5355}\u{5546}\u{54C1}";
    $statusDeliveredLabel = "\u{5DF2}\u{5B8C}\u{6210}\u{53D1}\u{8D27}";
    $pickupCodeLabel = "\u{53D6}\u{8D27}\u{7801}";
    $copyPickupCodeLabel = "\u{590D}\u{5236}\u{53D6}\u{8D27}\u{7801}";
    $pickupDeliveredHint = "\u{6362}\u{8BBE}\u{5907}\u{540E}\u{FF0C}\u{53EF}\u{5148}\u{7528}\u{8054}\u{7CFB}\u{65B9}\u{5F0F}\u{627E}\u{5230}\u{8BA2}\u{5355}\u{FF0C}\u{518D}\u{8F93}\u{5165}\u{8FD9} 6 \u{4F4D}\u{53D6}\u{8D27}\u{7801}\u{67E5}\u{770B}\u{5361}\u{5BC6}\u{3002}";
    $deliveryTitle = "\u{53D1}\u{8D27}\u{5361}\u{5BC6}";
    $copyLabel = "\u{4E00}\u{952E}\u{590D}\u{5236}";
    $paidHint = "\u{8BA2}\u{5355}\u{5DF2}\u{652F}\u{4ED8}\u{FF0C}\u{7CFB}\u{7EDF}\u{6B63}\u{5728}\u{81EA}\u{52A8}\u{53D1}\u{8D27}\u{3002}";
    $undeliveredHint = "\u{8BA2}\u{5355}\u{6682}\u{672A}\u{53D1}\u{8D27}\u{3002}";
    $qrErrorLabel = "\u{4E8C}\u{7EF4}\u{7801}\u{751F}\u{6210}\u{5931}\u{8D25}\u{FF0C}\u{8BF7}\u{5237}\u{65B0}\u{9875}\u{9762}\u{91CD}\u{8BD5}\u{3002}";
    $qrLoadErrorLabel = "\u{4E8C}\u{7EF4}\u{7801}\u{52A0}\u{8F7D}\u{5931}\u{8D25}\u{FF0C}\u{8BF7}\u{5237}\u{65B0}\u{9875}\u{9762}\u{91CD}\u{8BD5}\u{3002}";
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} - {{ $siteName }}</title>
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
                <a class="storefront-topbar__action storefront-topbar__action--ghost" href="{{ route('order.query', ['order_no' => $orderData['order_no'], 'contact' => $orderData['contact']]) }}">
                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                        <path d="M6 3.5A2.5 2.5 0 0 0 3.5 6v12A2.5 2.5 0 0 0 6 20.5h12a2.5 2.5 0 0 0 2.5-2.5V9.56a2.5 2.5 0 0 0-.73-1.77l-3.56-3.56A2.5 2.5 0 0 0 14.44 3.5H6Zm0 1.5h8v3.5A1.5 1.5 0 0 0 15.5 10H19v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9.5.56L17.94 8H15.5a.5.5 0 0 1-.5-.5V5.06Z" fill="currentColor"/>
                    </svg>
                    <span>{{ $queryLabel }}</span>
                </a>
                @if ($orderData['product_sku'])
                    <a class="storefront-topbar__action" href="{{ route('products.show', ['product' => $orderData['product_sku']]) }}">
                        <span>{{ $buyAgainLabel }}</span>
                    </a>
                @endif
            </div>
        </header>

        <main class="container storefront-order-page">
            @if (session('order_notice'))
                <div class="storefront-message storefront-message--success storefront-order-notice">
                    {{ session('order_notice') }}
                </div>
            @endif

            @if ($isPaymentState)
                <div class="storefront-payment-page">
                    <section class="storefront-payment-shell">
                        <div class="storefront-payment-shell__left">
                            <div class="storefront-payment-shell__qr-card">
                                <div class="order-payment-qr{{ $paymentQrCode === '' ? ' is-loading' : '' }}" data-payment-qr="{{ $paymentQrCode }}">
                                    @if ($paymentQrCode === '')
                                        <div class="order-payment-qr__placeholder">
                                            <span class="order-payment-qr__spinner" aria-hidden="true"></span>
                                            <strong>{{ $qrLoadingLabel }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="storefront-payment-shell__channel">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path d="M3.5 6A2.5 2.5 0 0 1 6 3.5h12A2.5 2.5 0 0 1 20.5 6v1.5h-17V6Zm17 3h-17V18A2.5 2.5 0 0 0 6 20.5h12a2.5 2.5 0 0 0 2.5-2.5V9ZM7 14.25h4v1.5H7v-1.5Z" fill="currentColor"/>
                                </svg>
                                <span>{{ $orderData['payment_channel_label'] }}</span>
                            </div>
                        </div>

                        <div class="storefront-payment-shell__center">
                            <div class="storefront-payment-shell__info-block">
                                <span>{{ $orderIdLabel }}</span>
                                <strong>{{ $orderData['order_no'] }}</strong>
                            </div>
                            <div class="storefront-payment-shell__info-block">
                                <span>{{ $paidAmountLabel }}</span>
                                <div class="storefront-payment-shell__amount">
                                    <em>&#165;</em>
                                    <strong>{{ $orderData['amount'] }}</strong>
                                </div>
                            </div>
                            <div class="storefront-payment-shell__info-block">
                                <span>{{ $countdownLabel }}</span>
                                <strong class="storefront-payment-shell__timer order-pay-countdown" data-expires-at="{{ $expiresAtMs }}">{{ str_pad((string) $remainingPaymentMinutes, 2, '0', STR_PAD_LEFT) }} : {{ str_pad((string) $remainingPaymentSeconds, 2, '0', STR_PAD_LEFT) }} : {{ str_pad((string) $remainingPaymentMilliseconds, 3, '0', STR_PAD_LEFT) }}</strong>
                            </div>
                        </div>

                        @if ($isMobileClient)
                            <a class="storefront-payment-shell__mobile-link" href="{{ $paymentQrCode !== '' ? $paymentQrCode : '#' }}" rel="nofollow" @if ($paymentQrCode === '') hidden @endif>{{ $openPaymentLabel }}</a>
                        @endif

                        <div class="storefront-payment-shell__divider"></div>

                        <div class="storefront-payment-shell__right">
                            <div class="storefront-payment-shell__meta">
                                <span>{{ $productNameLabel }}</span>
                                <strong>{{ $orderData['product_name'] ?? $fallbackProductLabel }}</strong>
                            </div>
                            <div class="storefront-payment-shell__meta">
                                <span>{{ $contactLabel }}</span>
                                <strong>{{ $orderData['contact'] }}</strong>
                            </div>
                            <div class="storefront-payment-shell__meta">
                                <span>{{ $quantityLabel }}</span>
                                <strong>{{ $orderData['quantity'] }} {{ $quantityUnitLabel }}</strong>
                            </div>
                        </div>
                    </section>
                </div>
            @else
                <section class="storefront-query-shell storefront-query-shell--result">
                    <article class="storefront-query-result">
                        <div class="storefront-query-result__head">
                            <div class="storefront-query-result__product">
                                <img src="{{ $imageUrl }}" alt="{{ $orderData['product_name'] ?? $fallbackProductLabel }}">
                                <div class="storefront-query-result__copy">
                                    @if ($orderData['status'] === $deliveredStatus)
                                        <div class="storefront-query-result__status-line">
                                            <span class="storefront-query-result__status-dot"></span>
                                            <span>{{ $statusDeliveredLabel }}</span>
                                        </div>
                                    @endif
                                    <h2>{{ $orderData['product_name'] ?? $fallbackProductLabel }}</h2>
                                </div>
                            </div>
                        </div>

                        <div class="storefront-query-result__body">
                            <div class="storefront-query-result__meta">
                                <div class="storefront-query-result__meta-row"><span>{{ $contactLabel }}</span><strong>{{ $orderData['contact'] }}</strong></div>
                                <div class="storefront-query-result__meta-row"><span>{{ $amountLabel }}</span><strong class="storefront-query-result__amount">&#165;{{ $orderData['amount'] }}</strong></div>
                                <div class="storefront-query-result__meta-row"><span>{{ $createdAtLabel }}</span><strong>{{ $orderData['created_at'] ?? '--' }}</strong></div>
                            </div>

                            <div class="storefront-query-result__delivery">
                                @if ($pickupCodeDisplay !== '')
                                    <div class="storefront-query-result__pickup">
                                        <div class="storefront-query-result__delivery-head">
                                            <span>{{ $pickupCodeLabel }}</span>
                                            <button type="button" class="storefront-copy-button" data-copy-text="{{ $pickupCodeDisplay }}">{{ $copyPickupCodeLabel }}</button>
                                        </div>
                                        <div class="storefront-query-result__delivery-body storefront-query-result__delivery-body--pickup">
                                            <pre>{{ $pickupCodeDisplay }}</pre>
                                            <p>{{ $pickupDeliveredHint }}</p>
                                        </div>
                                    </div>
                                @endif
                                <div class="storefront-query-result__delivery-head">
                                    <span>{{ $deliveryTitle }}</span>
                                    @if ($deliveryContent !== '')
                                        <button type="button" class="storefront-copy-button" data-copy-text="{{ $deliveryContent }}">{{ $copyLabel }}</button>
                                    @endif
                                </div>
                                <div class="storefront-query-result__delivery-body">
                                    @if ($deliveryContent !== '')
                                        <pre>{{ $deliveryContent }}</pre>
                                    @elseif ($orderData['status'] === $paidStatus)
                                        <p>{{ $paidHint }}</p>
                                    @else
                                        <p>{{ $undeliveredHint }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            @endif
        </main>
    </div>

    @if ($isPaymentState)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const countdownNode = document.querySelector('[data-expires-at]');
                const qrContainer = document.querySelector('[data-payment-qr]');
                const mobileLink = document.querySelector('.storefront-payment-shell__mobile-link');
                const statusUrl = @json(route('orders.status', ['order' => $orderData['order_no']]));
                const paymentStartUrl = @json($paymentUrl);
                const paymentEnabled = @json($paymentEnabled);
                const qrLoadingLabel = @json($qrLoadingLabel);
                const qrErrorLabel = @json($qrErrorLabel);
                const qrLoadErrorLabel = @json($qrLoadErrorLabel);
                let paymentQrCode = @json($paymentQrCode);
                let syncing = false;
                let syncTimer = null;
                let countdownTimer = null;
                let nextPollDelay = 5000;
                let bootstrappingPayment = false;

                const pad = (value, size) => String(Math.max(0, value)).padStart(size, '0');

                const setMobileLink = (qrCode) => {
                    if (!mobileLink) return;

                    const trimmedCode = typeof qrCode === 'string' ? qrCode.trim() : '';
                    mobileLink.href = trimmedCode !== '' ? trimmedCode : '#';
                    mobileLink.hidden = trimmedCode === '';
                };

                const setQrLoadingState = (message = qrLoadingLabel) => {
                    if (!qrContainer) return;

                    qrContainer.classList.add('is-loading');
                    qrContainer.classList.remove('is-error');
                    qrContainer.innerHTML = `
                        <div class="order-payment-qr__placeholder">
                            <span class="order-payment-qr__spinner" aria-hidden="true"></span>
                            <strong>${message}</strong>
                        </div>
                    `;
                    setMobileLink('');
                };

                const setQrErrorState = (message = qrErrorLabel) => {
                    if (!qrContainer) return;

                    qrContainer.classList.remove('is-loading');
                    qrContainer.classList.add('is-error');
                    qrContainer.innerHTML = `
                        <div class="order-payment-qr__placeholder">
                            <strong>${message}</strong>
                        </div>
                    `;
                    setMobileLink('');
                };

                const ensureQrLibrary = () => {
                    if (window.QRCode) {
                        return Promise.resolve(window.QRCode);
                    }

                    return new Promise((resolve, reject) => {
                        const existing = document.querySelector('script[data-qrcode-lib]');
                        if (existing) {
                            existing.addEventListener('load', () => resolve(window.QRCode), { once: true });
                            existing.addEventListener('error', reject, { once: true });
                            return;
                        }

                        const script = document.createElement('script');
                        script.src = '/vendor/qrcode.min.js';
                        script.async = true;
                        script.dataset.qrcodeLib = 'true';
                        script.onload = () => resolve(window.QRCode);
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                };

                const renderQrCode = async (qrCode) => {
                    const trimmedCode = typeof qrCode === 'string' ? qrCode.trim() : '';
                    if (!qrContainer || trimmedCode === '') return;

                    paymentQrCode = trimmedCode;
                    qrContainer.dataset.paymentQr = trimmedCode;
                    qrContainer.classList.remove('is-loading', 'is-error');
                    qrContainer.innerHTML = '';

                    const QRCode = await ensureQrLibrary();
                    if (!QRCode) {
                        throw new Error('missing QRCode');
                    }

                    new QRCode(qrContainer, {
                        text: trimmedCode,
                        width: qrContainer.clientWidth || 176,
                        height: qrContainer.clientHeight || 176,
                        correctLevel: QRCode.CorrectLevel.M,
                    });

                    setMobileLink(trimmedCode);
                };

                const updateCountdown = () => {
                    if (!countdownNode) return;

                    const expiresAt = Number(countdownNode.dataset.expiresAt || 0);
                    const diff = Math.max(0, expiresAt - Date.now());
                    const minutes = Math.floor(diff / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    const milliseconds = diff % 1000;
                    countdownNode.textContent = `${pad(minutes, 2)} : ${pad(seconds, 2)} : ${pad(milliseconds, 3)}`;
                };

                const clearSyncTimer = () => {
                    if (syncTimer) {
                        window.clearTimeout(syncTimer);
                        syncTimer = null;
                    }
                };

                const scheduleSync = (delay = nextPollDelay) => {
                    if (!paymentEnabled) return;
                    clearSyncTimer();
                    syncTimer = window.setTimeout(syncStatus, Math.max(500, delay));
                };

                const bootstrapPayment = async () => {
                    if (!paymentEnabled || !paymentStartUrl || paymentQrCode !== '' || bootstrappingPayment) {
                        return;
                    }

                    bootstrappingPayment = true;
                    setQrLoadingState();

                    try {
                        const response = await fetch(paymentStartUrl, {
                            headers: {
                                'Accept': 'text/html,application/xhtml+xml',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            cache: 'no-store'
                        });

                        if (!response.ok) {
                            throw new Error('payment bootstrap failed');
                        }

                        scheduleSync(500);
                    } catch (error) {
                        setQrErrorState();
                    } finally {
                        bootstrappingPayment = false;
                    }
                };

                const syncStatus = async () => {
                    if (!paymentEnabled) return;
                    if (syncing) {
                        scheduleSync(nextPollDelay);
                        return;
                    }

                    syncing = true;
                    try {
                        const response = await fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            cache: 'no-store'
                        });

                        if (!response.ok) return;

                        const data = await response.json();
                        if (data && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }

                        if (data && typeof data.payment_qr_code === 'string' && data.payment_qr_code.trim() !== '' && data.payment_qr_code.trim() !== paymentQrCode) {
                            try {
                                await renderQrCode(data.payment_qr_code);
                            } catch (error) {
                                setQrErrorState(qrLoadErrorLabel);
                            }
                        }

                        if (data && Number.isFinite(Number(data.poll_after_ms)) && Number(data.poll_after_ms) > 0) {
                            nextPollDelay = Number(data.poll_after_ms);
                        }
                    } catch (error) {
                        nextPollDelay = Math.min(nextPollDelay + 2000, 15000);
                    } finally {
                        syncing = false;
                        if (!document.hidden) scheduleSync(nextPollDelay);
                    }
                };

                updateCountdown();
                countdownTimer = window.setInterval(updateCountdown, 50);
                setMobileLink(paymentQrCode);

                if (paymentEnabled) {
                    const triggerSync = function () {
                        if (paymentQrCode === '') {
                            bootstrapPayment();
                        }
                        scheduleSync(paymentQrCode === '' ? 700 : 1400);
                    };

                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden) {
                            triggerSync();
                        } else {
                            clearSyncTimer();
                        }
                    });
                    window.addEventListener('focus', triggerSync);
                    window.addEventListener('pageshow', triggerSync);
                    document.addEventListener('resume', triggerSync, true);
                    window.addEventListener('online', triggerSync);
                    if (paymentQrCode === '') {
                        window.setTimeout(function () {
                            bootstrapPayment();
                        }, 60);
                    }
                    scheduleSync(paymentQrCode === '' ? 1800 : 3200);
                }

                window.addEventListener('pagehide', function () {
                    clearSyncTimer();
                    if (countdownTimer) window.clearInterval(countdownTimer);
                }, { once: true });
            });
        </script>
    @endif
</body>
</html>
