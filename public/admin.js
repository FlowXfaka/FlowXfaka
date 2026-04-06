(() => {
    const MAIN_SELECTOR = '[data-admin-main]';
    const NAV_SELECTOR = '[data-admin-nav]';
    const STYLES_SELECTOR = '#admin-page-styles';
    const SCRIPTS_SELECTOR = '#admin-page-scripts';
    const TOAST_SELECTOR = '#admin-page-toast-data';
    const SHELL_SELECTOR = '[data-admin-shell]';
    const LOADING_CLASS = 'admin-ui-busy';
    const cache = new Map();
    const prefetchJobs = new Map();
    let navigateController = null;

    const currentMain = () => document.querySelector(MAIN_SELECTOR);
    const currentNav = () => document.querySelector(NAV_SELECTOR);
    const currentStyles = () => document.querySelector(STYLES_SELECTOR);
    const currentScripts = () => document.querySelector(SCRIPTS_SELECTOR);
    const currentToastData = () => document.querySelector(TOAST_SELECTOR);
    const showAdminConfirm = (options = {}) => {
        if (typeof window.showAdminConfirm === 'function') {
            return window.showAdminConfirm(options);
        }

        return Promise.resolve(false);
    };

    const isModifiedEvent = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

    const parseUrl = (value) => {
        try {
            return new URL(value, window.location.href);
        } catch {
            return null;
        }
    };

    const normalizeUrl = (value) => {
        const url = parseUrl(value);
        if (!url) {
            return null;
        }
        url.hash = '';
        return url.toString();
    };

    const isAdminUrl = (url) => Boolean(
        url
        && /^https?:$/.test(url.protocol)
        && url.origin === window.location.origin
        && url.pathname.startsWith('/admin')
    );

    const shouldBypassSpa = () => false;

    const getConfirmOptions = (form) => {
        const message = (form.dataset.confirmMessage || '').trim();
        const messageHtml = (form.dataset.confirmMessageHtml || '').trim();
        if (!message && !messageHtml) {
            return null;
        }

        const confirmDelaySeconds = Number(form.dataset.confirmDelaySeconds || form.dataset.confirmDelay || 0);

        return {
            title: (form.dataset.confirmTitle || '请确认操作').trim(),
            message: message || messageHtml.replace(/<[^>]+>/g, '').trim(),
            messageHtml,
            allowHtml: messageHtml !== '',
            confirmText: (form.dataset.confirmConfirmText || '确认').trim(),
            cancelText: (form.dataset.confirmCancelText || '取消').trim(),
            variant: (form.dataset.confirmVariant || 'default').trim(),
            confirmDelaySeconds: Number.isFinite(confirmDelaySeconds) ? Math.max(0, confirmDelaySeconds) : 0,
        };
    };

    const requestConfirmedSubmit = (form) => {
        form.dataset.confirmApproved = '1';

        if (typeof form.requestSubmit === 'function') {
            const submitter = form.__adminSubmitter;
            if (submitter instanceof HTMLElement && submitter.form === form) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
            return;
        }

        HTMLFormElement.prototype.submit.call(form);
    };

    const escapeHtml = (value) => (value ?? '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const escapeSelectorValue = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(/["\\]/g, '\\$&');
    };

    const getFirstFormControl = (value) => {
        if (value instanceof HTMLElement) {
            return value;
        }

        if (typeof RadioNodeList !== 'undefined' && value instanceof RadioNodeList) {
            return Array.from(value).find((item) => item instanceof HTMLElement) || null;
        }

        return null;
    };

    const getFieldNameCandidates = (key) => {
        const normalized = String(key || '').trim();
        if (normalized === '') {
            return [];
        }

        const candidates = [normalized];
        if (normalized.includes('.')) {
            const segments = normalized.split('.');
            const root = segments.shift();
            candidates.push(`${root}${segments.map((segment) => `[${segment}]`).join('')}`);
        }

        return [...new Set(candidates)];
    };

    const getFieldErrorContainer = (node) => {
        if (!(node instanceof HTMLElement)) {
            return null;
        }

        return node.closest('[data-field-target], .admin-form-field, .admin-product-field, .admin-settings-field, .admin-editor-input-shell, .admin-editor-upload-wrap, .admin-settings-upload, .admin-settings-option-card, .admin-settings-toolbar-shell, .admin-editor-toolbar-shell, .admin-detail-tags-shell, .admin-radio-row--choices, .admin-checkbox-row--toggle')
            || node;
    };

    const clearAdminFormErrors = (form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.querySelectorAll('.is-invalid').forEach((node) => {
            node.classList.remove('is-invalid');
            node.removeAttribute('data-error-message');
            if (node instanceof HTMLInputElement || node instanceof HTMLTextAreaElement || node instanceof HTMLSelectElement) {
                node.removeAttribute('aria-invalid');
            }
        });
    };

    const bindAdminFormErrorReset = (form) => {
        if (!(form instanceof HTMLFormElement) || form.dataset.errorResetBound === '1') {
            return;
        }

        const clearTargetError = (target) => {
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const container = getFieldErrorContainer(target);
            if (container) {
                container.classList.remove('is-invalid');
                container.removeAttribute('data-error-message');
            }

            if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement) {
                target.classList.remove('is-invalid');
                target.removeAttribute('aria-invalid');
            }
        };

        form.addEventListener('input', (event) => clearTargetError(event.target), true);
        form.addEventListener('change', (event) => clearTargetError(event.target), true);
        form.dataset.errorResetBound = '1';
    };

    const resolveFieldTarget = (form, key, fieldMap = {}) => {
        if (!(form instanceof HTMLFormElement)) {
            return null;
        }

        const normalized = String(key || '').trim();
        if (normalized === '') {
            return null;
        }

        const mapped = fieldMap[normalized];
        if (mapped instanceof HTMLElement) {
            return mapped;
        }
        if (typeof mapped === 'function') {
            return mapped(form, normalized) || null;
        }
        if (typeof mapped === 'string' && mapped.trim() !== '') {
            return form.querySelector(mapped) || document.querySelector(mapped);
        }

        const explicitTarget = form.querySelector(`[data-field-target="${escapeSelectorValue(normalized)}"]`);
        if (explicitTarget) {
            return explicitTarget;
        }

        for (const candidate of getFieldNameCandidates(normalized)) {
            const control = getFirstFormControl(form.elements.namedItem(candidate));
            if (!control) {
                continue;
            }

            if (control instanceof HTMLInputElement && control.type === 'radio') {
                return control.closest('.admin-radio-row--choices')
                    || getFieldErrorContainer(control);
            }

            if (control instanceof HTMLInputElement && control.type === 'checkbox') {
                return control.closest('.admin-checkbox-row--toggle')
                    || getFieldErrorContainer(control);
            }

            return getFieldErrorContainer(control);
        }

        return null;
    };

    const markFieldInvalid = (target, message) => {
        if (!(target instanceof HTMLElement)) {
            return;
        }

        target.classList.add('is-invalid');
        if (message) {
            target.setAttribute('data-error-message', String(message));
        }

        if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement) {
            target.setAttribute('aria-invalid', 'true');
        }

        target.querySelectorAll('input, textarea, select').forEach((control) => {
            if (control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement || control instanceof HTMLSelectElement) {
                control.setAttribute('aria-invalid', 'true');
            }
        });
    };

    const focusFieldTarget = (target) => {
        if (!(target instanceof HTMLElement)) {
            return;
        }

        target.scrollIntoView({ block: 'center', behavior: 'smooth' });

        const focusable = target.matches('input, textarea, select, button')
            ? target
            : target.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), button:not([disabled])');

        if (focusable instanceof HTMLElement) {
            window.setTimeout(() => {
                focusable.focus({ preventScroll: true });
            }, 180);
        }
    };

    window.applyAdminFormErrors = ({
        form,
        errors,
        fieldMap = {},
        title = '保存失败',
        badgeText = '保存失败',
        confirmText = '知道了',
        showDialog = true,
    } = {}) => {
        if (!(form instanceof HTMLFormElement) || !errors || typeof errors !== 'object') {
            return;
        }

        bindAdminFormErrorReset(form);
        clearAdminFormErrors(form);

        const entries = Object.entries(errors).filter(([key, value]) => {
            if (String(key || '').trim() === '') {
                return false;
            }

            if (Array.isArray(value)) {
                return value.length > 0;
            }

            return Boolean(value);
        });

        if (!entries.length) {
            return;
        }

        const messages = [];
        let firstTarget = null;

        entries.forEach(([key, value]) => {
            const list = Array.isArray(value) ? value : [value];
            const firstMessage = String(list.find((item) => String(item || '').trim() !== '') || '').trim();
            list.forEach((item) => {
                const message = String(item || '').trim();
                if (message !== '' && !messages.includes(message)) {
                    messages.push(message);
                }
            });

            const target = resolveFieldTarget(form, key, fieldMap);
            if (target) {
                markFieldInvalid(target, firstMessage);
                if (!firstTarget) {
                    firstTarget = target;
                }
            }
        });

        if (firstTarget) {
            focusFieldTarget(firstTarget);
        }

        if (showDialog && typeof window.showAdminConfirm === 'function' && messages.length > 0) {
            const messageHtml = messages.map((message) => `<span>${escapeHtml(message)}</span>`).join('<br>');
            window.setTimeout(() => {
                void window.showAdminConfirm({
                    title,
                    messageHtml,
                    allowHtml: true,
                    confirmText,
                    showCancel: false,
                    showHead: false,
                    badgeText,
                });
            }, 0);
        }
    };

    const setFormSubmitting = (form, busy) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const submitter = form.__adminSubmitter instanceof HTMLElement
            ? form.__adminSubmitter
            : form.querySelector('button[type="submit"], input[type="submit"]');

        if (!(submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)) {
            if (busy) {
                form.dataset.submitting = '1';
            } else {
                delete form.dataset.submitting;
            }
            return;
        }

        const getLabel = () => submitter instanceof HTMLInputElement
            ? submitter.value
            : submitter.textContent || '';

        const setLabel = (value) => {
            if (submitter instanceof HTMLInputElement) {
                submitter.value = value;
            } else {
                submitter.textContent = value;
            }
        };

        if (busy) {
            form.dataset.submitting = '1';
            if (!submitter.dataset.idleLabel) {
                submitter.dataset.idleLabel = getLabel().trim() || '提交';
            }
            submitter.disabled = true;
            submitter.classList.add('is-submitting');
            setLabel('处理中...');
            return;
        }

        delete form.dataset.submitting;
        submitter.disabled = false;
        submitter.classList.remove('is-submitting');
        if (submitter.dataset.idleLabel) {
            setLabel(submitter.dataset.idleLabel);
        }
    };

    const setBusy = (busy) => {
        const main = currentMain();
        document.body.classList.toggle(LOADING_CLASS, busy);
        if (main) {
            main.setAttribute('aria-busy', busy ? 'true' : 'false');
        }
    };

    const createPayload = ({ title, mainHtml, navHtml, stylesHtml, scriptsHtml, toastJson }) => ({
        title,
        mainHtml,
        navHtml,
        stylesHtml,
        scriptsHtml,
        toastJson,
    });

    const payloadFromDocument = (doc) => {
        const main = doc.querySelector(MAIN_SELECTOR);
        const nav = doc.querySelector(NAV_SELECTOR);
        const styles = doc.querySelector(STYLES_SELECTOR);
        const scripts = doc.querySelector(SCRIPTS_SELECTOR);
        const toast = doc.querySelector(TOAST_SELECTOR);

        if (!main) {
            return null;
        }

        return createPayload({
            title: doc.title,
            mainHtml: main.outerHTML,
            navHtml: nav ? nav.innerHTML : '',
            stylesHtml: styles ? styles.innerHTML : '',
            scriptsHtml: scripts ? scripts.innerHTML : '',
            toastJson: toast ? (toast.textContent || '[]') : '[]',
        });
    };

    const payloadFromCurrentPage = () => {
        const main = currentMain();
        if (!main) {
            return null;
        }

        return createPayload({
            title: document.title,
            mainHtml: main.outerHTML,
            navHtml: currentNav() ? currentNav().innerHTML : '',
            stylesHtml: currentStyles() ? currentStyles().innerHTML : '',
            scriptsHtml: currentScripts() ? currentScripts().innerHTML : '',
            toastJson: currentToastData() ? (currentToastData().textContent || '[]') : '[]',
        });
    };

    const payloadFromHtml = (html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return payloadFromDocument(doc);
    };

    const remember = (url, payload) => {
        if (url && payload) {
            cache.set(url, {
                ...payload,
                toastJson: '[]',
            });
        }
    };

    const syncDynamicStyles = (payload) => {
        const slot = currentStyles();
        if (slot) {
            slot.innerHTML = payload.stylesHtml || '';
        }
    };

    const runScripts = (payload) => {
        const slot = currentScripts();
        if (!slot) {
            return;
        }

        slot.innerHTML = '';
        if (!payload.scriptsHtml) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = payload.scriptsHtml;

        Array.from(template.content.querySelectorAll('script')).forEach((script) => {
            const nextScript = document.createElement('script');
            Array.from(script.attributes).forEach((attribute) => {
                nextScript.setAttribute(attribute.name, attribute.value);
            });
            nextScript.textContent = script.textContent || '';
            slot.appendChild(nextScript);
        });
    };

    const syncToastData = (payload) => {
        const slot = currentToastData();
        if (!slot) {
            return;
        }

        slot.textContent = payload.toastJson || '[]';
    };

    const buildMainNode = (payload) => {
        const template = document.createElement('template');
        template.innerHTML = (payload.mainHtml || '').trim();
        return template.content.firstElementChild;
    };

    const syncDocument = (payload) => {
        const main = currentMain();
        const nav = currentNav();
        const importedMain = buildMainNode(payload);

        if (!main || !importedMain) {
            throw new Error('missing admin main');
        }

        document.title = payload.title;
        syncDynamicStyles(payload);
        syncToastData(payload);

        main.replaceWith(importedMain);

        if (nav) {
            nav.innerHTML = payload.navHtml || '';
        }

        runScripts(payload);
        window.consumeAdminPageToast?.();
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        scheduleWarmup();
    };

    const fetchPayload = async (targetUrl, { method = 'GET', body = null, controller = null } = {}) => {
        const response = await fetch(targetUrl, {
            method,
            body,
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const contentType = response.headers.get('content-type') || '';
        const html = await response.text();

        if (!contentType.includes('text/html')) {
            throw new Error('non-html response');
        }

        const payload = payloadFromHtml(html);
        const finalUrl = normalizeUrl(response.url || targetUrl);
        remember(finalUrl, payload);
        return { payload, finalUrl };
    };

    const prefetch = (targetUrl) => {
        const normalized = normalizeUrl(targetUrl);
        if (!normalized || cache.has(normalized) || prefetchJobs.has(normalized)) {
            return;
        }

        const job = fetchPayload(normalized)
            .catch(() => null)
            .finally(() => {
                prefetchJobs.delete(normalized);
            });

        prefetchJobs.set(normalized, job);
    };

    const collectPrefetchLinks = () => {
        const nav = currentNav();
        if (!nav) {
            return [];
        }

        const currentUrl = normalizeUrl(window.location.href);
        const links = [];
        nav.querySelectorAll('a[href]').forEach((link) => {
            if (link.target && link.target !== '_self') {
                return;
            }
            const normalized = normalizeUrl(link.href);
            const url = parseUrl(normalized);
            if (!normalized || !isAdminUrl(url) || shouldBypassSpa(url) || normalized === currentUrl || links.includes(normalized)) {
                return;
            }
            links.push(normalized);
        });
        return links;
    };

    const scheduleWarmup = () => {
        const links = collectPrefetchLinks();
        links.forEach((url, index) => {
            window.setTimeout(() => prefetch(url), 80 + index * 140);
        });
    };

    const navigate = async (url, { replace = false, historyMode = 'auto', useCache = true, method = 'GET', body = null } = {}) => {
        const targetUrl = normalizeUrl(url);
        if (!targetUrl) {
            return;
        }

        try {
            if (method === 'GET' && useCache && cache.has(targetUrl)) {
                syncDocument(cache.get(targetUrl));
                if (historyMode !== 'none') {
                    if (replace) {
                        window.history.replaceState({}, '', targetUrl);
                    } else {
                        window.history.pushState({}, '', targetUrl);
                    }
                }
                return;
            }

            if (navigateController) {
                navigateController.abort();
            }
            navigateController = new AbortController();

            setBusy(true);
            const result = await fetchPayload(targetUrl, {
                method,
                body,
                controller: navigateController,
            });

            syncDocument(result.payload);

            if (method !== 'GET') {
                cache.clear();
                remember(result.finalUrl, result.payload);
            }

            if (historyMode !== 'none') {
                if (replace || method !== 'GET') {
                    window.history.replaceState({}, '', result.finalUrl);
                } else {
                    window.history.pushState({}, '', result.finalUrl);
                }
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(targetUrl);
            }
        } finally {
            setBusy(false);
        }
    };

    document.addEventListener('click', (event) => {
        const submitter = event.target.closest('button, input[type="submit"]');
        if (
            (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)
            && submitter.form
        ) {
            submitter.form.__adminSubmitter = submitter;
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

        const url = parseUrl(link.href);
        if (!isAdminUrl(url)) {
            return;
        }
        if (shouldBypassSpa(url)) {
            return;
        }

        event.preventDefault();
        navigate(url.toString());
    });

    document.addEventListener('mouseover', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        const url = parseUrl(link.href);
        if (!isAdminUrl(url)) {
            return;
        }
        if (shouldBypassSpa(url)) {
            return;
        }
        prefetch(url.toString());
    });

    document.addEventListener('focusin', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        const url = parseUrl(link.href);
        if (!isAdminUrl(url)) {
            return;
        }
        if (shouldBypassSpa(url)) {
            return;
        }
        prefetch(url.toString());
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        const confirmOptions = getConfirmOptions(form);
        const confirmApproved = form.dataset.confirmApproved === '1';
        if (confirmApproved) {
            delete form.dataset.confirmApproved;
        }

        if (confirmOptions && !confirmApproved) {
            event.preventDefault();
            const confirmed = await showAdminConfirm(confirmOptions);
            if (!confirmed) {
                return;
            }

            requestConfirmedSubmit(form);
            return;
        }

        if (form.dataset.noSpa !== undefined) {
            setFormSubmitting(form, true);
            return;
        }

        const action = parseUrl(form.getAttribute('action') || window.location.href);
        if (!isAdminUrl(action)) {
            return;
        }
        if (shouldBypassSpa(action)) {
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
            setFormSubmitting(form, true);
            await navigate(action.toString());
            if (form.isConnected) {
                setFormSubmitting(form, false);
            }
            return;
        }

        setFormSubmitting(form, true);
        await navigate(action.toString(), {
            method,
            body: formData,
            replace: true,
            useCache: false,
        });
        if (form.isConnected) {
            setFormSubmitting(form, false);
        }
    });

    window.addEventListener('popstate', () => {
        const currentUrl = normalizeUrl(window.location.href);
        if (currentUrl && cache.has(currentUrl)) {
            syncDocument(cache.get(currentUrl));
            return;
        }
        navigate(window.location.href, { replace: true, historyMode: 'none' });
    });

    if (document.querySelector(SHELL_SELECTOR)) {
        remember(normalizeUrl(window.location.href), payloadFromCurrentPage());
        scheduleWarmup();
    }
})();
