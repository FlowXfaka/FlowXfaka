const waitForWangEditor = (attempts = 120) => new Promise((resolve) => {
    const check = () => {
        if (window.wangEditor) {
            resolve(window.wangEditor);
            return;
        }
        if (attempts <= 0) {
            resolve(null);
            return;
        }
        attempts -= 1;
        window.setTimeout(check, 50);
    };
    check();
});

const normalizeToolbarButtons = (toolbarHost) => {
    if (!toolbarHost) {
        return;
    }

    toolbarHost.querySelectorAll('button').forEach((button) => {
        if (button.getAttribute('type') !== 'button') {
            button.setAttribute('type', 'button');
        }
    });
};

const observeToolbarButtons = (toolbarHost) => {
    normalizeToolbarButtons(toolbarHost);
    const observer = new MutationObserver(() => normalizeToolbarButtons(toolbarHost));
    observer.observe(toolbarHost, { childList: true, subtree: true });
    return observer;
};

const restoreSelectionForToolbar = (richEditor) => {
    if (!richEditor) {
        return;
    }

    try {
        richEditor.restoreSelection();
        richEditor.focus();
    } catch {}
};

const preserveSelectionOnToolbar = (event, toolbarHost, richEditor) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target || !toolbarHost?.contains(target)) {
        return;
    }
    if (target.closest('input, textarea, select')) {
        return;
    }
    restoreSelectionForToolbar(richEditor);
    event.preventDefault();
};

const handleToolbarActivation = (event, toolbarHost, richEditor) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target || !toolbarHost?.contains(target)) {
        return;
    }
    if (target.closest('input, textarea, select')) {
        return;
    }
    restoreSelectionForToolbar(richEditor);
};

const escapeHtmlAttribute = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');

const showEditorError = (error, richEditor) => {
    const message = error instanceof Error ? error.message : String(error || '图片上传失败');
    if (richEditor?.alert) {
        richEditor.alert(message, 'error');
        return;
    }
    window.alert(message);
};

const uploadEditorImageFile = async ({
    file,
    csrfToken,
    uploadUrl,
    invalidTypeMessage = '请选择图片文件',
    oversizeMessage = '图片不能超过 5MB',
    defaultErrorMessage = '图片上传失败',
}) => {
    if (!file) {
        return '';
    }
    if (!file.type.startsWith('image/')) {
        throw new Error(invalidTypeMessage);
    }
    if (file.size > 5 * 1024 * 1024) {
        throw new Error(oversizeMessage);
    }

    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
        credentials: 'same-origin',
    });

    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.url) {
        throw new Error(result.message || defaultErrorMessage);
    }

    return result.url;
};

const insertUploadedImage = ({ richEditor, url, alt = '', syncOutput }) => {
    if (!richEditor || !url) {
        return;
    }

    restoreSelectionForToolbar(richEditor);
    if (!richEditor.selection) {
        richEditor.focus(true);
    }

    richEditor.dangerouslyInsertHtml(
        `<p><img src="${escapeHtmlAttribute(url)}" alt="${escapeHtmlAttribute(alt)}"></p>`
    );
    syncOutput();
};
