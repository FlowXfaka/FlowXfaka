@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
    $accountErrors = $errors->getBag('adminAccountUpdate');
    $openAccountDialog = $accountErrors->any();
    $adminToastEntries = [];
    $pushAdminToast = static function (array &$entries, mixed $message, string $state = 'success'): void {
        $content = trim((string) $message);

        if ($content === '') {
            return;
        }

        $entries[] = [
            'message' => $content,
            'state' => $state,
        ];
    };
    $containsToastKeyword = static function (mixed $message, array $keywords): bool {
        $content = trim((string) $message);

        if ($content === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            if (mb_strpos($content, $keyword) !== false) {
                return true;
            }
        }

        return false;
    };

    $productNotice = session('product_notice');
    $cardNotice = session('card_saved');
    $orderNotice = session('order_notice');
    $paymentNotice = session('payment_notice');
    $settingsNotice = session('settings_notice');
    $accountSaved = session('admin_account_status') === 'saved';

    $pushAdminToast(
        $adminToastEntries,
        $productNotice,
        $containsToastKeyword($productNotice, ["\u{65E0}\u{6CD5}", "\u{4E0D}\u{53EF}", "\u{5931}\u{8D25}"]) ? 'warning' : 'success'
    );
    $pushAdminToast($adminToastEntries, $cardNotice, 'success');
    $pushAdminToast(
        $adminToastEntries,
        $orderNotice,
        $containsToastKeyword($orderNotice, ["\u{4E0D}\u{8DB3}", "\u{4E0D}\u{5B58}\u{5728}", "\u{4E0D}\u{53EF}", "\u{5931}\u{8D25}"]) ? 'warning' : 'success'
    );
    $pushAdminToast($adminToastEntries, $paymentNotice, 'success');
    $pushAdminToast($adminToastEntries, $settingsNotice, 'success');

    if ($accountSaved) {
        $pushAdminToast($adminToastEntries, "\u{540E}\u{53F0}\u{8D26}\u{53F7}\u{5DF2}\u{4FDD}\u{5B58}\u{3002}", 'success');
    }

    $adminToastPayload = json_encode($adminToastEntries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]';
    $adminCssVersion = @filemtime(public_path('admin.css')) ?: time();
    $adminJsVersion = @filemtime(public_path('admin.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} - {{ $siteName }}</title>
    <meta name="description" content="&#21457;&#36135;&#31449;&#21518;&#21488;&#31649;&#29702;&#12290;">
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="stylesheet" href="{{ asset('admin.css') }}?v={{ $adminCssVersion }}">
    <script src="{{ asset('admin.js') }}?v={{ $adminJsVersion }}" defer></script>
</head>
<body>
    <div class="admin-shell" data-admin-shell>
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <span class="admin-brand-mark admin-brand-mark--image">
                    <img src="{{ $siteBrowserIconUrl }}" alt="" loading="eager" decoding="async">
                </span>
                <div>
                    <strong>{{ $siteName }}</strong>
                    <span>&#21518;&#21488;&#31649;&#29702;</span>
                </div>
            </div>

            <nav class="admin-nav" data-admin-nav aria-label="&#21518;&#21488;&#23548;&#33322;">
                <a class="{{ request()->routeIs('admin.overview') ? 'is-active' : '' }}" href="{{ route('admin.overview') }}">&#27010;&#35272;</a>
                <a class="{{ request()->routeIs('admin.products*') ? 'is-active' : '' }}" href="{{ route('admin.products') }}">&#21830;&#21697;&#31649;&#29702;</a>
                <a class="{{ request()->routeIs('admin.orders*') ? 'is-active' : '' }}" href="{{ route('admin.orders') }}">&#35746;&#21333;&#31649;&#29702;</a>
                <a class="{{ request()->routeIs('admin.payments*') ? 'is-active' : '' }}" href="{{ route('admin.payments') }}">&#25903;&#20184;&#31649;&#29702;</a>
                <a class="{{ request()->routeIs('admin.settings*') ? 'is-active' : '' }}" href="{{ route('admin.settings') }}">&#31449;&#28857;&#35774;&#32622;</a>
            </nav>

                <div class="admin-side-note">
                @auth
                    <button
                        type="button"
                        class="admin-side-user admin-side-user-trigger"
                        data-account-open
                        title="当前账号：{{ auth()->user()->name }}"
                        aria-label="修改信息，当前账号：{{ auth()->user()->name }}"
                    >
                        <strong>修改信息</strong>
                    </button>
                    <form method="POST" action="{{ route('admin.logout') }}" class="admin-logout-form" data-no-spa>
                        @csrf
                        <button type="submit" class="admin-side-logout">&#36864;&#20986;&#30331;&#24405;</button>
                    </form>
                @endauth
                <span>&#24555;&#25463;&#20837;&#21475;</span>
                <a href="{{ route('home') }}">&#36820;&#22238;&#21069;&#21488;</a>
            </div>
        </aside>

        <main class="admin-main" data-admin-main>
            @yield('content')
        </main>
    </div>

    @auth
        <div class="admin-account-modal {{ $openAccountDialog ? 'is-open' : '' }}" data-account-modal>
            <div class="admin-account-backdrop" data-account-close></div>
            <div class="admin-account-dialog" role="dialog" aria-modal="true">
                @if ($accountErrors->any())
                    <div class="admin-account-alert">{{ $accountErrors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('admin.account.update') }}" class="admin-account-form" data-no-spa>
                    @csrf
                    <label>
                        <span>&#36134;&#21495;</span>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" autocomplete="username">
                    </label>
                    <label>
                        <span>&#24403;&#21069;&#23494;&#30721;</span>
                        <input type="password" name="current_password" autocomplete="current-password">
                    </label>
                    <label>
                        <span>&#26032;&#23494;&#30721;</span>
                        <input type="password" name="password" autocomplete="new-password">
                    </label>
                    <label>
                        <span>&#30830;&#35748;&#26032;&#23494;&#30721;</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                    </label>
                    <div class="admin-account-actions">
                        <button type="button" class="admin-account-secondary" data-account-close>&#21462;&#28040;</button>
                        <button type="submit" class="admin-account-primary">&#20445;&#23384;</button>
                    </div>
                </form>
            </div>
        </div>
    @endauth

    <div class="admin-confirm-modal" data-confirm-modal data-variant="default">
        <div class="admin-confirm-backdrop" data-confirm-close></div>
        <div class="admin-confirm-dialog" role="dialog" aria-modal="true" aria-describedby="admin-confirm-message">
            <div class="admin-confirm-badge" data-confirm-badge>请确认</div>
            <p id="admin-confirm-message" class="admin-confirm-message" data-confirm-message>确定继续吗？</p>
            <div class="admin-confirm-actions">
                <button type="button" class="admin-confirm-secondary" data-confirm-cancel>取消</button>
                <button type="button" class="admin-confirm-primary" data-confirm-accept>确认</button>
            </div>
        </div>
    </div>

    <div class="admin-route-progress" data-admin-route-progress aria-hidden="true"><span></span></div>
    <div class="admin-toast-stack" data-admin-toast-stack aria-live="polite" aria-atomic="true"></div>
    <script id="admin-page-toast-data" type="application/json">{!! $adminToastPayload !!}</script>

    <div id="admin-page-styles" hidden>@stack('head')</div>
    <div id="admin-page-scripts" hidden>@stack('scripts')</div>

    <script>
        (() => {
            const accountModal = document.querySelector('[data-account-modal]');
            const confirmModal = document.querySelector('[data-confirm-modal]');
            const toastStack = document.querySelector('[data-admin-toast-stack]');
            const toastDataNode = document.getElementById('admin-page-toast-data');
            let confirmResolver = null;
            let confirmRestoreTarget = null;
            let confirmCountdownTimer = null;
            let toastSeed = 0;

            const syncModalState = () => {
                const hasOpenModal = Boolean(document.querySelector('.admin-account-modal.is-open, .admin-confirm-modal.is-open'));
                document.body.classList.toggle('admin-modal-open', hasOpenModal);
            };

            const normalizeToastState = (value) => {
                const state = (value || 'success').toString().trim().toLowerCase();
                return ['success', 'warning', 'error'].includes(state) ? state : 'success';
            };

            const readPageToasts = () => {
                if (!toastDataNode) {
                    return [];
                }

                try {
                    const parsed = JSON.parse(toastDataNode.textContent || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch {
                    return [];
                }
            };

            const clearPageToasts = () => {
                if (toastDataNode) {
                    toastDataNode.textContent = '[]';
                }
            };

            const dismissToast = (toast) => {
                if (!(toast instanceof HTMLElement) || toast.dataset.closing === '1') {
                    return;
                }

                toast.dataset.closing = '1';
                toast.classList.remove('is-visible');
                window.setTimeout(() => {
                    toast.remove();
                }, 220);
            };

            const setAccountModalOpen = (open) => {
                if (!accountModal) {
                    return;
                }

                accountModal.classList.toggle('is-open', open);
                syncModalState();
            };

            const clearConfirmCountdown = () => {
                if (confirmCountdownTimer) {
                    window.clearInterval(confirmCountdownTimer);
                    confirmCountdownTimer = null;
                }
            };

            const resetConfirmButton = () => {
                if (!confirmModal) {
                    return;
                }

                const confirmButton = confirmModal.querySelector('[data-confirm-accept]');
                if (!confirmButton) {
                    return;
                }

                clearConfirmCountdown();
                confirmButton.disabled = false;
                confirmButton.removeAttribute('aria-disabled');
                confirmButton.textContent = confirmButton.dataset.baseText || '确认';
            };

            const closeConfirmModal = (confirmed) => {
                if (!confirmModal) {
                    return;
                }

                resetConfirmButton();
                confirmModal.classList.remove('is-open');
                syncModalState();

                const resolve = confirmResolver;
                confirmResolver = null;

                const restoreTarget = confirmRestoreTarget;
                confirmRestoreTarget = null;

                if (restoreTarget && typeof restoreTarget.focus === 'function') {
                    window.setTimeout(() => restoreTarget.focus(), 0);
                }

                if (typeof resolve === 'function') {
                    resolve(confirmed);
                }
            };

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-account-open]')) {
                    setAccountModalOpen(true);
                    return;
                }
                if (event.target.closest('[data-account-close]')) {
                    setAccountModalOpen(false);
                    return;
                }
                if (event.target.closest('[data-confirm-accept]')) {
                    closeConfirmModal(true);
                    return;
                }
                if (event.target.closest('[data-confirm-close]') || event.target.closest('[data-confirm-cancel]')) {
                    closeConfirmModal(false);
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (confirmModal?.classList.contains('is-open')) {
                    event.preventDefault();
                    closeConfirmModal(false);
                    return;
                }

                if (accountModal?.classList.contains('is-open')) {
                    event.preventDefault();
                    setAccountModalOpen(false);
                }
            });

            window.showAdminConfirm = ({
                title = '请确认操作',
                message = '确定继续吗？',
                messageHtml = '',
                allowHtml = false,
                confirmText = '确认',
                cancelText = '取消',
                variant = 'default',
                confirmDelaySeconds = 0,
                showCancel = true,
                showHead = true,
                badgeText = '',
            } = {}) => {
                if (!confirmModal) {
                    return Promise.resolve(false);
                }

                if (typeof confirmResolver === 'function') {
                    confirmResolver(false);
                    confirmResolver = null;
                }

                confirmRestoreTarget = document.activeElement instanceof HTMLElement ? document.activeElement : null;

                const badge = confirmModal.querySelector('[data-confirm-badge]');
                const headNode = confirmModal.querySelector('.admin-confirm-head');
                const titleNode = confirmModal.querySelector('[data-confirm-title]');
                const messageNode = confirmModal.querySelector('[data-confirm-message]');
                const confirmButton = confirmModal.querySelector('[data-confirm-accept]');
                const cancelButton = confirmModal.querySelector('[data-confirm-cancel]');
                const closeButton = confirmModal.querySelector('[data-confirm-close]');
                const resolvedBadgeText = badgeText || (variant === 'danger' ? '危险操作' : '请确认');
                const baseConfirmText = confirmText;
                const shouldShowCancel = Boolean(showCancel) && cancelText.trim() !== '';
                const shouldShowHead = Boolean(showHead);

                confirmModal.dataset.variant = variant;
                if (badge) {
                    badge.textContent = resolvedBadgeText;
                }
                if (headNode) {
                    headNode.hidden = !shouldShowHead;
                }
                if (titleNode) {
                    titleNode.textContent = title;
                }
                if (messageNode) {
                    if (allowHtml && messageHtml) {
                        messageNode.innerHTML = messageHtml;
                    } else {
                        messageNode.textContent = message;
                    }
                }
                if (confirmButton) {
                    confirmButton.dataset.baseText = baseConfirmText;
                    confirmButton.textContent = baseConfirmText;
                    confirmButton.disabled = false;
                    confirmButton.removeAttribute('aria-disabled');
                }
                if (cancelButton) {
                    cancelButton.textContent = cancelText;
                    cancelButton.hidden = !shouldShowCancel;
                    cancelButton.setAttribute('aria-hidden', shouldShowCancel ? 'false' : 'true');
                }
                if (closeButton) {
                    closeButton.hidden = !shouldShowHead;
                    closeButton.setAttribute('aria-hidden', shouldShowHead ? 'false' : 'true');
                }

                confirmModal.classList.add('is-open');
                syncModalState();

                window.setTimeout(() => {
                    confirmButton?.focus();
                }, 0);

                resetConfirmButton();

                if (confirmButton && confirmDelaySeconds > 0) {
                    let remaining = Math.ceil(confirmDelaySeconds);
                    confirmButton.disabled = true;
                    confirmButton.setAttribute('aria-disabled', 'true');
                    confirmButton.textContent = `${baseConfirmText}（${remaining}秒）`;

                    confirmCountdownTimer = window.setInterval(() => {
                        remaining -= 1;

                        if (remaining <= 0) {
                            resetConfirmButton();
                            return;
                        }

                        confirmButton.textContent = `${baseConfirmText}（${remaining}秒）`;
                    }, 1000);
                }

                return new Promise((resolve) => {
                    confirmResolver = resolve;
                });
            };

            window.showAdminToast = ({
                message = '',
                state = 'success',
                duration = 2400,
            } = {}) => {
                const content = (message || '').toString().trim();

                if (!toastStack || content === '') {
                    return;
                }

                const tone = normalizeToastState(state);
                const badgeTextMap = {
                    success: '\u64CD\u4F5C\u6210\u529F',
                    warning: '\u8BF7\u6CE8\u610F',
                    error: '\u64CD\u4F5C\u5931\u8D25',
                };
                const iconMap = {
                    success: '\u2713',
                    warning: '!',
                    error: '\u00D7',
                };
                const toast = document.createElement('div');

                toast.id = `admin-toast-${++toastSeed}`;
                toast.className = 'admin-toast';
                toast.dataset.state = tone;
                toast.innerHTML = `
                    <div class="admin-toast__icon" aria-hidden="true">${iconMap[tone] || iconMap.success}</div>
                    <div class="admin-toast__content">
                        <strong>${badgeTextMap[tone] || badgeTextMap.success}</strong>
                        <p></p>
                    </div>
                `;

                const messageNode = toast.querySelector('p');
                if (messageNode) {
                    messageNode.textContent = content;
                }

                toast.addEventListener('click', () => dismissToast(toast));
                toastStack.appendChild(toast);

                window.requestAnimationFrame(() => {
                    toast.classList.add('is-visible');
                });

                window.setTimeout(() => {
                    dismissToast(toast);
                }, Math.max(1600, Number(duration) || 2400));
            };

            window.consumeAdminPageToast = () => {
                const entries = readPageToasts();

                if (entries.length === 0) {
                    return;
                }

                clearPageToasts();
                entries.forEach((entry, index) => {
                    window.setTimeout(() => {
                        window.showAdminToast(entry);
                    }, index * 140);
                });
            };

            window.addEventListener('admin:toast', (event) => {
                const detail = event.detail;

                if (Array.isArray(detail)) {
                    detail.forEach((entry, index) => {
                        window.setTimeout(() => window.showAdminToast(entry), index * 140);
                    });
                    return;
                }

                window.showAdminToast(detail || {});
            });

            syncModalState();
            window.setTimeout(() => {
                window.consumeAdminPageToast?.();
            }, 0);
        })();
    </script>
</body>
</html>
