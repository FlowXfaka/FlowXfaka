(() => {
    const ROOT_SELECTOR = '.page-shell';
    const CATEGORY_PILL_SELECTOR = '[data-category-slug]';
    const CATALOG_BODY_SELECTOR = '[data-product-body]';
    const LOADING_CLASS = 'is-route-loading';
    const ENTER_CLASS = 'is-route-entering';
    const DEFAULT_LOADING_MESSAGE = '\u52a0\u8f7d\u4e2d...';
    const PAY_LOADING_MESSAGE = '\u6b63\u5728\u6253\u5f00\u652f\u4ed8\u9875...';
    let activeRequest = null;
    const catalogCache = new Map();
    const pendingCatalogRequests = new Map();
    let catalogSearchToken = 0;
    let hasBooted = false;

    const isModifiedEvent = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

    const getHomePath = () => {
        const brandLink = document.querySelector('.storefront-brand[href]');
        return brandLink ? new URL(brandLink.href, window.location.href).pathname : '/';
    };

    const getRawCategorySlug = (url) => {
        const raw = url.searchParams.get('category');
        return typeof raw === 'string' ? raw.trim() : '';
    };

    const getDefaultCategorySlug = () => {
        const firstCategory = Array.from(document.querySelectorAll(CATEGORY_PILL_SELECTOR))
            .find((pill) => (pill.dataset.categorySlug || '') !== '');

        return firstCategory?.dataset.categorySlug || '';
    };

    const hasCategorySlug = (slug) => {
        return Array.from(document.querySelectorAll(CATEGORY_PILL_SELECTOR))
            .some((pill) => (pill.dataset.categorySlug || '') === slug);
    };

    const getCategorySlug = (url) => {
        const rawCategorySlug = getRawCategorySlug(url);

        if (rawCategorySlug === 'all') {
            return '';
        }

        if (rawCategorySlug !== '' && hasCategorySlug(rawCategorySlug)) {
            return rawCategorySlug;
        }

        return getDefaultCategorySlug();
    };

    const isCatalogNavigation = (url) => {
        return Boolean(document.querySelector(CATEGORY_PILL_SELECTOR) && document.querySelector('[data-product-body]') && url.pathname === getHomePath());
    };

    const escapeSelectorValue = (value) => {
        if (window.CSS?.escape) {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\]/g, '\\$&');
    };

    const getCatalogBody = () => document.querySelector(CATALOG_BODY_SELECTOR);

    const getCatalogLoader = () => document.querySelector('[data-catalog-loader]');

    const setCatalogLoaderMessage = (message) => {
        const label = document.querySelector('[data-catalog-loader-label]');
        if (label) {
            label.textContent = message || '切换中...';
        }
    };

    const getSelectedCatalogSlug = () => getCatalogBody()?.dataset.selectedCategory || '';

    const setSelectedCatalogSlug = (slug) => {
        const catalogBody = getCatalogBody();
        if (catalogBody) {
            catalogBody.dataset.selectedCategory = slug;
        }
    };

    const getRenderedCatalogSlug = () => getCatalogBody()?.dataset.currentCategory || '';

    const setRenderedCatalogSlug = (slug) => {
        const catalogBody = getCatalogBody();
        if (catalogBody) {
            catalogBody.dataset.currentCategory = slug;
        }
    };

    const setCatalogFetchingState = (fetching, message = '切换中...') => {
        const catalogBody = getCatalogBody();
        if (catalogBody) {
            catalogBody.classList.toggle('is-fetching', fetching);
        }

        const loader = getCatalogLoader();
        if (!loader) {
            return;
        }

        setCatalogLoaderMessage(message);

        if (fetching) {
            loader.hidden = false;
            loader.classList.add('is-active');
            loader.setAttribute('aria-hidden', 'false');
            return;
        }

        loader.classList.remove('is-active');
        loader.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => {
            if (!loader.classList.contains('is-active')) {
                loader.hidden = true;
            }
        }, 170);
    };

    const primeCatalogState = () => {
        catalogCache.clear();

        const catalogBody = getCatalogBody();
        if (!catalogBody) {
            return;
        }

        const panel = catalogBody.querySelector('[data-category-panel]');
        if (!panel) {
            return;
        }

        const slug = panel.dataset.categoryPanel || '';
        setRenderedCatalogSlug(slug);

        if (!catalogBody.dataset.selectedCategory) {
            catalogBody.dataset.selectedCategory = slug;
        }

        catalogCache.set(slug, panel.innerHTML);
    };

    const buildCatalogFragmentUrl = (categorySlug) => {
        const url = new URL(getHomePath(), window.location.origin);

        if (categorySlug === '') {
            url.searchParams.set('category', 'all');
        } else {
            url.searchParams.set('category', categorySlug);
        }

        url.searchParams.set('fragment', 'products');

        return url;
    };

    const fetchCatalogProducts = async (categorySlug) => {
        if (catalogCache.has(categorySlug)) {
            const cachedUrl = categorySlug === ''
                ? `${getHomePath()}?category=all`
                : `${getHomePath()}?category=${encodeURIComponent(categorySlug)}`;

            return {
                html: catalogCache.get(categorySlug),
                selectedCategorySlug: categorySlug,
                url: cachedUrl,
            };
        }

        if (pendingCatalogRequests.has(categorySlug)) {
            return pendingCatalogRequests.get(categorySlug);
        }

        const request = (async () => {
            const response = await fetch(buildCatalogFragmentUrl(categorySlug), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('catalog request failed');
            }

            const payload = await response.json();
            const normalizedSlug = typeof payload.selectedCategorySlug === 'string'
                ? payload.selectedCategorySlug
                : categorySlug;

            if (typeof payload.html === 'string') {
                catalogCache.set(normalizedSlug, payload.html);
            }

            return {
                html: typeof payload.html === 'string' ? payload.html : '',
                selectedCategorySlug: normalizedSlug,
                url: typeof payload.url === 'string' ? payload.url : buildCatalogFragmentUrl(normalizedSlug).toString(),
            };
        })();

        pendingCatalogRequests.set(categorySlug, request);

        try {
            return await request;
        } finally {
            pendingCatalogRequests.delete(categorySlug);
        }
    };

    const renderCatalogProducts = (html, selectedCategorySlug) => {
        const catalogBody = getCatalogBody();
        if (!catalogBody) {
            return;
        }

        catalogBody.innerHTML = `
            <section class="catalog-products-panel is-active" data-category-panel="${selectedCategorySlug}">
                ${html}
            </section>
        `;

        setRenderedCatalogSlug(selectedCategorySlug);
        setActiveCatalogPanel(selectedCategorySlug);
        animateCatalogPanel(selectedCategorySlug);
    };

    const ensureTransitionCover = () => {
        let cover = document.querySelector('.route-transition-cover');
        if (!cover) {
            cover = document.createElement('div');
            cover.className = 'route-transition-cover';
            cover.innerHTML = `
                <div class="route-transition-cover__dialog" role="status" aria-live="polite" aria-atomic="true">
                    <div class="route-transition-cover__surface" aria-hidden="true">
                        <span class="route-transition-cover__halo"></span>
                        <span class="route-transition-cover__ring route-transition-cover__ring--outer"></span>
                        <span class="route-transition-cover__ring route-transition-cover__ring--inner"></span>
                        <span class="route-transition-cover__core"></span>
                    </div>
                    <div class="route-transition-cover__label"></div>
                </div>
            `;
            document.body.appendChild(cover);
        }
        return cover;
    };

    const setLoadingMessage = (cover, message = DEFAULT_LOADING_MESSAGE) => {
        const label = cover.querySelector('.route-transition-cover__label');
        if (label) {
            label.textContent = message || DEFAULT_LOADING_MESSAGE;
        }
    };

    const setLoadingState = (loading, message = DEFAULT_LOADING_MESSAGE) => {
        document.body.classList.toggle(LOADING_CLASS, loading);
        const cover = ensureTransitionCover();
        setLoadingMessage(cover, message);
        cover.classList.toggle('is-active', loading);
    };

    const clearLoadingState = (controller = null) => {
        if (controller && activeRequest !== controller) {
            return;
        }

        document.body.classList.remove(LOADING_CLASS);
        document.querySelector('.route-transition-cover')?.classList.remove('is-active');
        if (!controller || activeRequest === controller) {
            activeRequest = null;
        }
    };

    const syncMeta = (nextDocument) => {
        document.title = nextDocument.title;
        const currentMeta = document.querySelector('meta[name="description"]');
        const nextMeta = nextDocument.querySelector('meta[name="description"]');
        if (currentMeta && nextMeta) {
            currentMeta.setAttribute('content', nextMeta.getAttribute('content') || '');
        }
    };

    const syncBody = (nextDocument, controller = null) => {
        const nextBody = nextDocument.body;
        const nextShell = nextBody.querySelector(ROOT_SELECTOR);
        if (!nextShell) {
            throw new Error('missing page shell');
        }

        document.documentElement.lang = nextDocument.documentElement.lang || document.documentElement.lang;

        const preservedClasses = [];
        if (document.body.classList.contains(LOADING_CLASS)) {
            preservedClasses.push(LOADING_CLASS);
        }

        document.body.className = [nextBody.className, ...preservedClasses].filter(Boolean).join(' ');

        const style = nextBody.getAttribute('style');
        if (style) {
            document.body.setAttribute('style', style);
        } else {
            document.body.removeAttribute('style');
        }

        nextShell.classList.add(ENTER_CLASS);

        const currentShell = document.querySelector(ROOT_SELECTOR);
        if (!currentShell) {
            document.body.innerHTML = nextBody.innerHTML;
            clearLoadingState(controller);
            return;
        }

        currentShell.replaceWith(nextShell);

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                clearLoadingState(controller);
            });
        });

        window.setTimeout(() => {
            nextShell.classList.remove(ENTER_CLASS);
        }, 220);
    };

    const setActiveCategory = (selectedCategorySlug) => {
        document.querySelectorAll(CATEGORY_PILL_SELECTOR).forEach((pill) => {
            pill.classList.toggle('is-active', pill.dataset.categorySlug === selectedCategorySlug);
        });
    };

    const setActiveCatalogPanel = (selectedCategorySlug) => {
        const panels = document.querySelectorAll('[data-category-panel]');
        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.categoryPanel === selectedCategorySlug);
        });

        const active = document.querySelector('[data-category-panel].is-active');
        if (!active && panels[0]) {
            panels[0].classList.add('is-active');
        }
    };

    const animateCatalogPanel = (selectedCategorySlug) => {
        const activePanel = document.querySelector(`[data-category-panel="${escapeSelectorValue(selectedCategorySlug)}"]`) || document.querySelector('[data-category-panel].is-active');
        if (!activePanel) {
            return;
        }

        activePanel.classList.remove('is-catalog-entering');
        void activePanel.offsetWidth;
        activePanel.classList.add('is-catalog-entering');
        window.setTimeout(() => activePanel.classList.remove('is-catalog-entering'), 380);
    };

    const navigateCatalog = async (url) => {
        const nextUrl = new URL(typeof url === 'string' ? url : url.toString(), window.location.href);
        const rawCategorySlug = getRawCategorySlug(nextUrl);
        const requestedCategorySlug = getCategorySlug(nextUrl);
        const searchInput = document.querySelector('[data-storefront-search]');
        const searchClear = document.querySelector('[data-storefront-search-clear]');

        if (searchInput) {
            searchInput.value = '';
        }
        if (searchClear) {
            searchClear.hidden = true;
        }

        setSelectedCatalogSlug(requestedCategorySlug);
        setActiveCategory(requestedCategorySlug);

        const cleanUrl = rawCategorySlug === 'all'
            ? `${nextUrl.pathname}?category=all`
            : (rawCategorySlug !== '' && hasCategorySlug(rawCategorySlug)
                ? `${nextUrl.pathname}?category=${encodeURIComponent(rawCategorySlug)}`
                : nextUrl.pathname);

        setCatalogFetchingState(true, '切换中...');

        try {
            const payload = await fetchCatalogProducts(requestedCategorySlug);
            renderCatalogProducts(payload.html, payload.selectedCategorySlug);
            setSelectedCatalogSlug(payload.selectedCategorySlug);
            setActiveCategory(payload.selectedCategorySlug);
            const emptyState = document.querySelector('[data-storefront-search-empty]');
            if (emptyState) {
                emptyState.hidden = true;
            }
            window.history.pushState({}, '', payload.url || cleanUrl);
        } catch (error) {
            window.location.assign(nextUrl.toString());
        } finally {
            setCatalogFetchingState(false);
        }
    };

    const normalizeStepper = (input) => {
        const min = Math.max(1, Number(input.min || 1));
        const max = Math.max(min, Number(input.max || min));
        const current = Number.parseInt(input.value, 10);
        const next = Number.isFinite(current) ? current : min;
        input.value = Math.min(max, Math.max(min, next));
    };

    const syncOrderTotal = (input) => {
        const row = input.closest('.checkout-quantity-row');
        const totalNode = row?.querySelector('[data-order-total]');
        if (!row || !totalNode) {
            return;
        }

        const unitPrice = Number.parseFloat(row.dataset.unitPrice || '0');
        const quantity = Math.max(1, Number.parseInt(input.value, 10) || 1);
        totalNode.textContent = `¥${(unitPrice * quantity).toFixed(2)}`;
    };

    const syncCheckoutPaymentOptions = () => {
        document.querySelectorAll('.checkout-payment-options').forEach((group) => {
            group.querySelectorAll('.checkout-payment-option').forEach((option) => {
                const input = option.querySelector('input[type="radio"]');
                option.classList.toggle('is-selected', Boolean(input?.checked));
            });
        });
    };

    const resolveLoadingMessage = (target) => {
        const explicitMessage = typeof target?.dataset?.loadingLabel === 'string'
            ? target.dataset.loadingLabel.trim()
            : '';
        if (explicitMessage !== '') {
            return explicitMessage;
        }

        if (target instanceof HTMLFormElement && target.matches('.storefront-checkout-form')) {
            return PAY_LOADING_MESSAGE;
        }

        return DEFAULT_LOADING_MESSAGE;
    };

    const setSubmitPendingState = (form, pending, message = PAY_LOADING_MESSAGE) => {
        const submitButton = form.querySelector('[type="submit"]');
        if (!(submitButton instanceof HTMLButtonElement || submitButton instanceof HTMLInputElement)) {
            return;
        }

        const labelNode = submitButton.querySelector('.storefront-pay-button__content span:last-child');
        if (pending) {
            if (submitButton.dataset.pendingState === 'true') {
                return;
            }

            submitButton.dataset.pendingState = 'true';
            submitButton.dataset.originalDisabled = submitButton.disabled ? 'true' : 'false';
            if (labelNode) {
                submitButton.dataset.originalLabel = labelNode.textContent || '';
                labelNode.textContent = message;
            }
            submitButton.disabled = true;
            submitButton.classList.add('is-processing');
            return;
        }

        if (labelNode && typeof submitButton.dataset.originalLabel === 'string') {
            labelNode.textContent = submitButton.dataset.originalLabel;
        }
        submitButton.disabled = submitButton.dataset.originalDisabled === 'true';
        submitButton.classList.remove('is-processing');
        delete submitButton.dataset.pendingState;
        delete submitButton.dataset.originalDisabled;
        delete submitButton.dataset.originalLabel;
    };

    const clearPendingSubmits = () => {
        document.querySelectorAll('form[data-route-loading="true"]').forEach((form) => {
            form.dataset.routeLoading = 'false';
            setSubmitPendingState(form, false);
        });
    };

    const initOrderTotals = () => {
        document.querySelectorAll('.checkout-quantity-row').forEach((row) => {
            const input = row.querySelector('.quantity-stepper__input');
            if (!input) {
                return;
            }
            normalizeStepper(input);
            syncOrderTotal(input);
        });
    };

    const ensureQrLibrary = () => {
        if (window.QRCode) {
            return Promise.resolve(window.QRCode);
        }

        const existing = document.querySelector('script[data-qrcode-lib]');
        if (existing) {
            return new Promise((resolve, reject) => {
                existing.addEventListener('load', () => resolve(window.QRCode), { once: true });
                existing.addEventListener('error', reject, { once: true });
            });
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/vendor/qrcode.min.js';
            script.defer = true;
            script.dataset.qrcodeLib = 'true';
            script.onload = () => resolve(window.QRCode);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    };

    const initPaymentQr = async () => {
        const container = document.querySelector('[data-payment-qr]');
        if (!container) {
            return;
        }

        const qrText = container.getAttribute('data-payment-qr') || '';
        if (!qrText) {
            return;
        }

        container.innerHTML = '';

        try {
            const QRCode = await ensureQrLibrary();
            if (!QRCode) {
                throw new Error('missing QRCode');
            }

            new QRCode(container, {
                text: qrText,
                width: 220,
                height: 220,
                correctLevel: QRCode.CorrectLevel.M,
            });
        } catch (error) {
            container.innerHTML = `<div class="order-payment-qr-fallback"><a href="${qrText}" target="_blank" rel="noreferrer">打开支付链接</a></div>`;
        }
    };

    const initOrderQuerySwitch = () => {
        const root = document.querySelector('[data-query-switch]');
        if (!root || root.dataset.boundQuerySwitch === 'true') {
            return;
        }

        const tabs = Array.from(root.querySelectorAll('[data-query-tab]'));
        const orderInput = root.querySelector('[data-query-input="order"]');
        const contactInput = root.querySelector('[data-query-input="contact"]');
        if (!tabs.length || !orderInput || !contactInput) {
            return;
        }

        const syncMode = (mode) => {
            root.dataset.mode = mode;
            tabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.queryTab === mode);
            });

            const orderActive = mode === 'order';
            orderInput.classList.toggle('query-hidden', !orderActive);
            orderInput.disabled = !orderActive;
            contactInput.classList.toggle('query-hidden', orderActive);
            contactInput.disabled = orderActive;
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => syncMode(tab.dataset.queryTab));
        });

        root.dataset.boundQuerySwitch = 'true';
        syncMode(root.dataset.mode === 'contact' ? 'contact' : 'order');
    };

    const applyStorefrontSearch = async () => {
        const input = document.querySelector('[data-storefront-search]');
        const emptyState = document.querySelector('[data-storefront-search-empty]');
        const query = (input?.value || '').trim().toLowerCase();
        const catalogBody = getCatalogBody();

        if (!catalogBody) {
            return;
        }

        if (!query) {
            const selectedCategorySlug = getSelectedCatalogSlug();

            if (getRenderedCatalogSlug() !== selectedCategorySlug) {
                setCatalogFetchingState(true, '恢复中...');

                try {
                    const payload = await fetchCatalogProducts(selectedCategorySlug);
                    renderCatalogProducts(payload.html, payload.selectedCategorySlug);
                    setSelectedCatalogSlug(payload.selectedCategorySlug);
                    setActiveCategory(payload.selectedCategorySlug);
                } catch (error) {
                    return;
                } finally {
                    setCatalogFetchingState(false);
                }
            } else {
                setActiveCategory(selectedCategorySlug);
                setActiveCatalogPanel(selectedCategorySlug);
                Array.from(catalogBody.querySelectorAll('[data-product-card]')).forEach((card) => {
                    card.hidden = false;
                });
            }

            if (emptyState) {
                emptyState.hidden = true;
            }
            return;
        }

        const searchToken = ++catalogSearchToken;
        setActiveCategory('');

        if (getRenderedCatalogSlug() !== '') {
            setCatalogFetchingState(true, '搜索中...');

            try {
                const payload = await fetchCatalogProducts('');
                if (searchToken !== catalogSearchToken) {
                    return;
                }
                renderCatalogProducts(payload.html, payload.selectedCategorySlug);
            } catch (error) {
                return;
            } finally {
                setCatalogFetchingState(false);
            }
        }

        if (searchToken !== catalogSearchToken) {
            return;
        }

        const allPanel = catalogBody.querySelector('[data-category-panel=""]') || catalogBody.querySelector('[data-category-panel]');
        if (!allPanel) {
            return;
        }

        setActiveCatalogPanel('');

        let visibleCount = 0;
        Array.from(allPanel.querySelectorAll('[data-product-card]')).forEach((card) => {
            const searchText = (card.dataset.productSearch || '').toLowerCase();
            const matched = searchText.includes(query);
            card.hidden = !matched;
            if (matched) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    };

    const initStorefrontSearch = () => {
        const input = document.querySelector('[data-storefront-search]');
        const clearButton = document.querySelector('[data-storefront-search-clear]');
        if (!input || input.dataset.boundSearch === 'true') {
            return;
        }

        const sync = () => {
            const hasValue = input.value.trim() !== '';
            if (clearButton) {
                clearButton.hidden = !hasValue;
            }
            void applyStorefrontSearch();
        };

        input.addEventListener('input', sync);
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                input.value = '';
                input.focus();
                sync();
            });
        }

        input.dataset.boundSearch = 'true';
        sync();
    };

    const initCopyButtons = () => {
        document.querySelectorAll('[data-copy-text]').forEach((button) => {
            if (button.dataset.boundCopy === 'true') {
                return;
            }

            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-copy-text') || '';
                if (!text) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(text);
                } catch (error) {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }

                const original = button.textContent;
                button.textContent = '已复制';
                window.setTimeout(() => {
                    button.textContent = original || '一键复制';
                }, 1800);
            });

            button.dataset.boundCopy = 'true';
        });
    };

    const initProductShare = () => {
        const shareButton = document.querySelector('[data-product-share]');
        const toast = document.querySelector('[data-copy-toast]');
        if (!shareButton || shareButton.dataset.boundShare === 'true') {
            return;
        }

        let toastTimer = null;
        let copiedTimer = null;

        const showToast = (message) => {
            if (!toast) {
                return;
            }

            toast.textContent = message;
            toast.classList.add('is-visible');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => {
                toast.classList.remove('is-visible');
            }, 1600);
        };

        const copyLink = async (url) => {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
                return true;
            }

            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                return document.execCommand('copy');
            } finally {
                textarea.remove();
            }
        };

        shareButton.addEventListener('click', async () => {
            const url = window.location.href;

            try {
                const copied = await copyLink(url);
                if (copied) {
                    shareButton.classList.add('is-copied');
                    showToast('已复制该商品链接');
                    window.clearTimeout(copiedTimer);
                    copiedTimer = window.setTimeout(() => {
                        shareButton.classList.remove('is-copied');
                    }, 1600);
                    return;
                }
            } catch {
            }

            window.prompt('复制链接', url);
        });

        shareButton.dataset.boundShare = 'true';
    };

    const initPage = () => {
        clearPendingSubmits();
        primeCatalogState();
        document.querySelectorAll('.quantity-stepper__input').forEach(normalizeStepper);
        initOrderTotals();
        syncCheckoutPaymentOptions();
        initPaymentQr();
        initOrderQuerySwitch();
        initStorefrontSearch();
        initCopyButtons();
        initProductShare();

        if (!hasBooted) {
            hasBooted = true;
            const shell = document.querySelector(ROOT_SELECTOR);
            if (shell && !shell.classList.contains(ENTER_CLASS)) {
                shell.classList.add(ENTER_CLASS);
                window.setTimeout(() => {
                    shell.classList.remove(ENTER_CLASS);
                }, 420);
            }
        }
    };

    const fetchPage = async (url, options = {}) => {
        if (activeRequest) {
            activeRequest.abort();
        }

        const controller = new AbortController();
        activeRequest = controller;
        setLoadingState(true, options.loadingLabel || DEFAULT_LOADING_MESSAGE);

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                },
                ...options,
            });

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            syncMeta(nextDocument);
            syncBody(nextDocument, controller);
            initPage();
            return response;
        } catch (error) {
            clearLoadingState(controller);
            throw error;
        }
    };

    const navigate = async (url, { replace = false, historyMode = 'auto', ...options } = {}) => {
        try {
            const response = await fetchPage(url, options);
            const finalUrl = response.url || url;

            if (historyMode !== 'none') {
                const method = (options.method || 'GET').toUpperCase();
                const useReplace = replace || method !== 'GET' ? finalUrl === window.location.href : false;

                if (useReplace) {
                    window.history.replaceState({}, '', finalUrl);
                } else {
                    window.history.pushState({}, '', finalUrl);
                }
            }

            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(typeof url === 'string' ? url : url.toString());
            }
        }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.quantity-stepper__button');
        if (button) {
            if (button.hasAttribute('onclick')) {
                return;
            }

            event.preventDefault();
            const stepper = button.closest('.quantity-stepper');
            const input = stepper?.querySelector('.quantity-stepper__input');
            if (!input) {
                return;
            }

            normalizeStepper(input);
            const delta = button.dataset.action === 'increase' ? 1 : -1;
            input.value = Number.parseInt(input.value, 10) + delta;
            normalizeStepper(input);
            syncOrderTotal(input);
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || isModifiedEvent(event)) {
            return;
        }

        if (link.target && link.target !== '_self') {
            return;
        }

        if (link.hasAttribute('download') || link.dataset.noSpa !== undefined) {
            return;
        }

        const nextUrl = new URL(link.href, window.location.href);
        if (nextUrl.origin !== window.location.origin) {
            return;
        }

        if (nextUrl.hash && nextUrl.pathname === window.location.pathname && nextUrl.search === window.location.search) {
            return;
        }

        if (link.matches(CATEGORY_PILL_SELECTOR) && isCatalogNavigation(nextUrl)) {
            event.preventDefault();
            const searchInput = document.querySelector('[data-storefront-search]');
            const hasActiveSearch = Boolean(searchInput && searchInput.value.trim() !== '');
            if (nextUrl.pathname === window.location.pathname && nextUrl.search === window.location.search && !hasActiveSearch) {
                return;
            }
            void navigateCatalog(nextUrl.toString());
            return;
        }

        event.preventDefault();
        navigate(nextUrl.toString());
    });

    document.addEventListener('input', (event) => {
        if (event.target.matches('.quantity-stepper__input')) {
            normalizeStepper(event.target);
            syncOrderTotal(event.target);
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('.checkout-payment-option input[type="radio"]')) {
            syncCheckoutPaymentOptions();
        }
    });

    document.addEventListener('blur', (event) => {
        if (event.target.matches('.quantity-stepper__input')) {
            normalizeStepper(event.target);
        }
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.noSpa !== undefined) {
            const action = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            if (action.origin !== window.location.origin) {
                return;
            }

            if (form.dataset.routeLoading === 'true') {
                event.preventDefault();
                return;
            }

            const loadingMessage = resolveLoadingMessage(form);
            form.dataset.routeLoading = 'true';
            setSubmitPendingState(form, true, loadingMessage);
            setLoadingState(true, loadingMessage);
            return;
        }

        const action = new URL(form.getAttribute('action') || window.location.href, window.location.href);
        if (action.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const formData = new FormData(form);

        if (method === 'GET') {
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (typeof value === 'string' && value.trim() !== '') {
                    params.append(key, value);
                }
            }
            action.search = params.toString();
            navigate(action.toString(), {
                loadingLabel: resolveLoadingMessage(form),
            });
            return;
        }

        navigate(action.toString(), {
            method,
            body: formData,
            replace: true,
            loadingLabel: resolveLoadingMessage(form),
        });
    });

window.FlowXSyncOrderTotal = (input) => {
        if (!input) {
            return;
        }
        normalizeStepper(input);
        syncOrderTotal(input);
    };

window.FlowXAdjustQuantity = (button, delta) => {
        const stepper = button?.closest('.quantity-stepper');
        const input = stepper?.querySelector('.quantity-stepper__input');
        if (!input) {
            return;
        }
        normalizeStepper(input);
        input.value = (Number.parseInt(input.value, 10) || 1) + Number(delta || 0);
        normalizeStepper(input);
        syncOrderTotal(input);
    };

    window.addEventListener('popstate', () => {
        navigate(window.location.href, { replace: true, historyMode: 'none' });
    });

    window.addEventListener('pageshow', () => {
        clearLoadingState();
        clearPendingSubmits();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPage, { once: true });
    } else {
        initPage();
    }
})();
