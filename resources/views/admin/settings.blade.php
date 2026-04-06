@extends('admin.layout')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/wangeditor/style.css') }}?v={{ @filemtime(public_path('vendor/wangeditor/style.css')) ?: time() }}">
<style>
.admin-settings-page{display:grid;gap:1.25rem}
.admin-settings-layout{display:grid;gap:1rem}
.admin-settings-form{display:grid;gap:1rem}
.admin-settings-hero-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(18rem,.9fr);gap:1rem;align-items:stretch}
.admin-settings-panel{display:grid;gap:1rem;padding:1.2rem 1.25rem;border:1px solid rgba(27,36,48,.08);border-radius:1.4rem;background:linear-gradient(180deg,rgba(255,255,255,.96),rgba(248,251,255,.82))}
.admin-settings-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem}
.admin-settings-panel-copy{display:grid;gap:.3rem}
.admin-settings-panel-copy h2{margin:0;font-size:1.15rem}
.admin-settings-panel-copy h2,
.admin-settings-option-card-head h3,
.admin-editor-toolbar-head h2{font-family:"Microsoft YaHei","PingFang SC","Noto Sans SC",sans-serif;font-weight:800;letter-spacing:-.02em;color:var(--ink)}
.admin-settings-field{display:grid;gap:.7rem}
.admin-settings-field-label{font-size:.96rem;font-weight:700;color:var(--ink)}
.admin-settings-input{width:100%;min-height:3rem;padding:0 1rem;border:1px solid rgba(27,36,48,.1);border-radius:1rem;background:rgba(255,255,255,.96);color:var(--ink);font:inherit}
.admin-settings-save-card{grid-template-rows:auto 1fr auto}
.admin-settings-save-meta{display:flex;flex-wrap:wrap;gap:.6rem}
.admin-settings-save-chip{display:inline-flex;align-items:center;justify-content:center;min-height:2.1rem;padding:0 .95rem;border-radius:999rem;background:rgba(204,106,71,.09);color:var(--accent-deep);font-size:.9rem;font-weight:700}
.admin-settings-submit{display:inline-flex;align-items:center;justify-content:center;width:100%;min-height:3.2rem;padding:0 1.25rem;border:0;border-radius:1rem;background:linear-gradient(135deg,#ff8846,#ff5b4e);color:#fff;font:inherit;font-weight:800;cursor:pointer;box-shadow:0 .9rem 1.8rem rgba(255,107,79,.2)}
.admin-settings-submit:hover{transform:translateY(-1px)}
.admin-settings-appearance-shell{display:grid;gap:1rem}
.admin-settings-appearance-grid{display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,.95fr) minmax(0,1.18fr);gap:1rem;align-items:start}
.admin-settings-option-card{display:grid;gap:.9rem;align-content:start;padding:1rem;border:1px solid rgba(27,36,48,.08);border-radius:1.2rem;background:rgba(255,255,255,.94)}
.admin-settings-option-card--wide{grid-column:auto}
.admin-settings-option-card-head{display:grid;gap:.3rem}
.admin-settings-option-card-head h3{margin:0;font-size:1rem;font-weight:800;color:var(--ink)}
.admin-settings-background-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:.9rem;align-items:start}
.admin-settings-background-grid.is-compact{grid-template-columns:minmax(0,1fr)}
.admin-settings-background-tools{display:grid;gap:.9rem}
.admin-settings-background-actions{display:grid;gap:.9rem;align-content:start}
.admin-settings-background-upload-panel.is-hidden{display:none}
.admin-settings-background-link-panel{display:grid;gap:.85rem}
.admin-settings-background-link-panel.is-hidden{display:none}
.admin-settings-inline-input{display:grid;grid-template-columns:minmax(0,1fr);gap:.75rem;align-items:start}
.admin-settings-inline-button{display:inline-flex;align-items:center;justify-content:center;min-height:3rem;padding:0 1rem;border:1px solid rgba(204,106,71,.18);border-radius:1rem;background:rgba(255,255,255,.96);color:var(--accent-deep);font:inherit;font-weight:700;cursor:pointer;transition:border-color .18s ease,background .18s ease,transform .18s ease}
.admin-settings-inline-button:hover{border-color:rgba(204,106,71,.32);background:rgba(204,106,71,.08)}
.admin-settings-inline-button:active{transform:translateY(1px)}
.admin-settings-background-actions .admin-settings-inline-button{justify-self:start;min-width:8rem}
.admin-settings-mode{display:flex;gap:.75rem;flex-wrap:wrap}
.admin-settings-mode button{min-height:2.75rem;padding:0 1rem;border:1px solid rgba(27,36,48,.1);border-radius:999rem;background:rgba(255,255,255,.82);color:var(--ink);font:inherit;font-weight:700;cursor:pointer}
.admin-settings-mode button.is-active{border-color:rgba(204,106,71,.24);background:rgba(204,106,71,.12);color:var(--accent-deep)}
.admin-settings-preview-wrap{display:grid;gap:.75rem}
.admin-settings-preview-wrap.is-hidden{display:none}
.admin-settings-upload{position:relative;width:min(100%,22rem);aspect-ratio:16/9;border:1px dashed rgba(27,36,48,.16);border-radius:1.25rem;background:rgba(246,249,253,.9);overflow:hidden;cursor:pointer}
.admin-settings-upload--square{width:min(100%,8.5rem);aspect-ratio:1/1}
.admin-settings-upload--full{width:min(100%,16rem)}
.admin-settings-upload input{position:absolute;inset:0;opacity:0;cursor:pointer}
.admin-settings-upload img{position:relative;z-index:1;display:block;width:100%;height:100%;object-fit:cover}
.admin-settings-upload-empty{position:absolute;inset:0;z-index:2;display:grid;place-items:center;padding:1rem;text-align:center;color:var(--muted)}
.admin-settings-upload-empty[hidden],.admin-settings-upload img[hidden]{display:none!important}
.admin-settings-upload-empty strong{display:block;font-size:2rem;color:var(--accent);line-height:1}
.admin-editor-toolbar-shell{border:1px solid rgba(27,36,48,.08);border-radius:1.25rem;overflow:hidden;background:rgba(255,255,255,.92)}
.admin-editor-toolbar-head{padding:1rem 1rem .85rem;border-bottom:1px solid rgba(27,36,48,.08)}
.admin-editor-toolbar-head h2{margin:0;font-size:1rem}
.admin-editor-wang{overflow:hidden;border-top:1px solid rgba(27,36,48,.08);background:rgba(255,255,255,.94)}
.admin-editor-wang-toolbar .w-e-toolbar{border:0!important;background:rgba(246,249,252,.88)!important;border-bottom:1px solid rgba(27,36,48,.08)!important}
.admin-editor-wang-surface{min-height:22rem}
.admin-editor-wang-surface .w-e-text-container{min-height:22rem!important;background:rgba(255,255,255,.96)!important}
.admin-editor-wang-surface .w-e-scroll{min-height:22rem!important}
.admin-editor-wang-surface .w-e-text-placeholder{top:1rem!important;color:rgba(100,116,139,.92)!important}
.admin-editor-wang-surface [data-slate-editor]{min-height:22rem!important;padding:1rem!important;line-height:1.85!important}
.admin-editor-wang-surface img{max-width:100%;border-radius:.85rem}
.admin-editor-wang-toolbar button,.admin-editor-wang-toolbar [role='button'],.admin-editor-wang-toolbar .w-e-bar-item{cursor:pointer!important;pointer-events:auto!important}
.admin-editor-toolbar-shell,
.admin-editor-wang,
.admin-editor-wang-toolbar,
.admin-editor-wang-toolbar .w-e-toolbar,
.admin-editor-wang-toolbar .w-e-bar,
.admin-editor-wang-toolbar .w-e-bar-item-group{overflow:visible!important}
.admin-editor-toolbar-shell,
.admin-editor-wang{position:relative}
.admin-editor-wang-toolbar{position:relative;z-index:40}
.admin-editor-wang-surface{position:relative;z-index:1}
.admin-editor-wang-toolbar .w-e-drop-panel,
.admin-editor-wang-toolbar .w-e-select-list,
.admin-editor-wang-toolbar .w-e-modal,
.admin-editor-wang-toolbar .w-e-panel-container,
.admin-editor-wang-toolbar .w-e-menu-tooltip-v5,
.admin-editor-wang .w-e-hover-bar-container{z-index:60!important}
@media (max-width:72rem){
    .admin-settings-hero-grid{grid-template-columns:1fr}
    .admin-settings-appearance-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .admin-settings-option-card--wide{grid-column:1/-1}
}
@media (max-width:48rem){
    .admin-settings-appearance-grid{grid-template-columns:1fr}
    .admin-settings-inline-input{grid-template-columns:1fr}
    .admin-settings-upload,
    .admin-settings-upload--square,
    .admin-settings-upload--full{width:100%}
}
</style>
@endpush

@section('content')
@php
    $brandIconMode = old('brand_icon_mode', $siteSettings->resolvedBrandIconPath() ? 'custom' : 'default');
    $currentBrandIconPath = $siteSettings->resolvedBrandIconPath();
    $currentBrandIconUrl = $currentBrandIconPath ? asset($currentBrandIconPath) : null;
    $backgroundMode = old('background_mode', $siteSettings->background_mode === 'custom' && $siteSettings->background_image_path ? 'custom' : 'default');
    $backgroundImageUrl = old('background_image_url', '');
    $backgroundLinkPanelOpen = $backgroundMode === 'custom' && trim((string) $backgroundImageUrl) !== '';
    $backgroundSource = $backgroundMode === 'default' ? 'default' : ($backgroundLinkPanelOpen ? 'link' : 'upload');
    $backgroundUploadPanelHidden = $backgroundSource === 'link';
    $frontendTextMode = old('frontend_text_mode', $siteSettings->resolvedFrontendTextMode());
    $currentBackgroundPath = $siteSettings->background_mode === 'custom' && is_string($siteSettings->background_image_path) && trim($siteSettings->background_image_path) !== ''
        ? trim($siteSettings->background_image_path)
        : null;
    $currentBackgroundUrl = $currentBackgroundPath ? asset($currentBackgroundPath) : null;
    $wangVersion = @filemtime(public_path('vendor/wangeditor/index.js')) ?: time();
@endphp

<div class="admin-settings-page">
    <section class="admin-card admin-settings-layout">
        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="admin-settings-form" data-settings-form data-no-spa>
            @csrf
            <input type="hidden" name="frontend_text_mode" value="{{ $frontendTextMode }}" data-text-mode-input>
            <input type="hidden" name="brand_icon_mode" value="{{ $brandIconMode }}" data-brand-icon-mode>
            <input type="hidden" name="remove_brand_icon" value="0" data-remove-brand-icon>
            <input type="hidden" name="background_mode" value="{{ $backgroundMode }}" data-background-mode>
            <input type="hidden" name="remove_background_image" value="0" data-remove-background>
            <textarea name="notice_html" hidden data-editor-output>{{ old('notice_html', \App\Support\RichTextSanitizer::sanitize((string) $siteSettings->notice_html)) }}</textarea>

            <div class="admin-settings-hero-grid">
                <section class="admin-settings-panel">
                    <div class="admin-settings-panel-head">
                        <div class="admin-settings-panel-copy">
                            <h2>基础信息</h2>
                        </div>
                    </div>
                    <div class="admin-settings-field" data-field-target="site_name">
                        <label class="admin-settings-field-label" for="site_name">站点名称</label>
                        <input id="site_name" class="admin-settings-input" type="text" name="site_name" maxlength="80" value="{{ old('site_name', $siteSettings->resolvedSiteName()) }}" required>
                    </div>
                    <div class="admin-settings-field" data-field-target="frontend_theme">
                        <label class="admin-settings-field-label" for="frontend_theme">前端模板</label>
                        <select id="frontend_theme" class="admin-settings-input" name="frontend_theme">
                            @foreach ($storefrontThemes as $themeKey => $themeLabel)
                                <option value="{{ $themeKey }}" @selected(old('frontend_theme', $siteSettings->resolvedFrontendTheme()) === $themeKey)>{{ $themeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <aside class="admin-settings-panel admin-settings-save-card">
                    <div class="admin-settings-panel-head">
                        <div class="admin-settings-panel-copy">
                            <h2>快速发布</h2>
                        </div>
                    </div>
                    <div class="admin-settings-save-meta">
                        <span class="admin-settings-save-chip">前台图标</span>
                        <span class="admin-settings-save-chip">前台背景</span>
                        <span class="admin-settings-save-chip">站点公告</span>
                    </div>
                    <button type="submit" class="admin-settings-submit">保存设置</button>
                </aside>
            </div>

            <section class="admin-settings-panel admin-settings-appearance-shell">
                <div class="admin-settings-panel-head">
                    <div class="admin-settings-panel-copy">
                        <h2>前台外观</h2>
                    </div>
                </div>

                <div class="admin-settings-appearance-grid">
                    <article class="admin-settings-option-card" data-field-target="brand_icon">
                        <div class="admin-settings-option-card-head">
                            <h3>前端站点图标</h3>
                        </div>
                        <div class="admin-settings-mode">
                            <button type="button" class="{{ $brandIconMode === 'default' ? 'is-active' : '' }}" data-brand-icon-switch="default">默认</button>
                            <button type="button" class="{{ $brandIconMode === 'custom' ? 'is-active' : '' }}" data-brand-icon-switch="custom">自定义</button>
                        </div>
                        <div class="admin-settings-preview-wrap {{ $brandIconMode === 'default' ? 'is-hidden' : '' }}" data-brand-icon-wrap>
                            <label class="admin-settings-upload admin-settings-upload--square">
                                <input type="file" name="brand_icon" accept=".png,.jpg,.jpeg,.webp,.gif,.avif" data-brand-icon-input>
                                <img src="{{ $currentBrandIconUrl ?? '' }}" alt="brand icon preview" data-brand-icon-preview {{ $currentBrandIconUrl ? '' : 'hidden' }}>
                                <div class="admin-settings-upload-empty" data-brand-icon-empty {{ $currentBrandIconUrl ? 'hidden' : '' }}>
                                    <div>
                                        <strong>+</strong>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </article>

                    <article class="admin-settings-option-card" data-field-target="frontend_text_mode">
                        <div class="admin-settings-option-card-head">
                            <h3>前端文字模式</h3>
                        </div>
                        <div class="admin-settings-mode">
                            <button type="button" class="{{ $frontendTextMode === 'light' ? 'is-active' : '' }}" data-text-mode-switch="light">白字模式</button>
                            <button type="button" class="{{ $frontendTextMode === 'dark' ? 'is-active' : '' }}" data-text-mode-switch="dark">黑字模式</button>
                        </div>
                    </article>

                    <article class="admin-settings-option-card admin-settings-option-card--wide" data-field-target="background_image">
                        <div class="admin-settings-option-card-head">
                            <h3>前端背景图</h3>
                        </div>
                        <div class="admin-settings-background-grid {{ $backgroundMode === 'default' ? 'is-compact' : '' }}" data-background-grid>
                            <div class="admin-settings-field">
                                <div class="admin-settings-mode">
                                    <button type="button" class="{{ $backgroundSource === 'default' ? 'is-active' : '' }}" data-background-source-switch="default">默认</button>
                                    <button type="button" class="{{ $backgroundSource === 'upload' ? 'is-active' : '' }}" data-background-source-switch="upload">本地上传</button>
                                    <button type="button" class="{{ $backgroundSource === 'link' ? 'is-active' : '' }}" data-background-source-switch="link">链接上传</button>
                                </div>
                            </div>
                            <div class="admin-settings-preview-wrap {{ $backgroundMode === 'default' ? 'is-hidden' : '' }}" data-background-wrap>
                                <div class="admin-settings-background-tools">
                                    <div class="admin-settings-field admin-settings-background-upload-panel {{ $backgroundUploadPanelHidden ? 'is-hidden' : '' }}" data-background-upload-panel>
                                        <span class="admin-settings-field-label">上传图片</span>
                                        <label class="admin-settings-upload admin-settings-upload--full">
                                            <input type="file" name="background_image" accept=".png,.jpg,.jpeg,.webp,.gif,.avif" data-background-input>
                                            <img src="{{ $currentBackgroundUrl ?? '' }}" alt="background preview" data-background-preview {{ $currentBackgroundUrl ? '' : 'hidden' }}>
                                            <div class="admin-settings-upload-empty" data-background-empty {{ $currentBackgroundUrl ? 'hidden' : '' }}>
                                                <div>
                                                    <strong>+</strong>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="admin-settings-background-actions admin-settings-background-link-panel {{ $backgroundLinkPanelOpen ? '' : 'is-hidden' }}" data-background-link-panel>
                                        <div class="admin-settings-field">
                                            <label class="admin-settings-field-label" for="background_image_url">图片链接</label>
                                            <div class="admin-settings-inline-input">
                                                <input
                                                    id="background_image_url"
                                                    class="admin-settings-input"
                                                    type="url"
                                                    name="background_image_url"
                                                    value="{{ $backgroundImageUrl }}"
                                                    placeholder="https://example.com/background.jpg"
                                                    inputmode="url"
                                                    autocomplete="off"
                                                    spellcheck="false"
                                                    data-background-url-input
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="admin-editor-toolbar-shell" data-field-target="notice_html">
                <div class="admin-editor-toolbar-head">
                    <h2>站点公告</h2>
                </div>
                <div class="admin-editor-wang">
                    <div class="admin-editor-wang-toolbar" id="admin-settings-editor-toolbar"></div>
                    <div class="admin-editor-wang-surface" id="admin-settings-editor"></div>
                </div>
            </section>

        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/wangeditor/index.js') }}?v={{ $wangVersion }}" defer></script>
<script>
(() => {
    const form = document.querySelector('[data-settings-form]');
    if (!form) return;
    const settingsFormErrors = @json($errors->getMessages());

    const editorHost = document.getElementById('admin-settings-editor');
    const toolbarHost = document.getElementById('admin-settings-editor-toolbar');
    const output = form.querySelector('[data-editor-output]');
    const textModeInput = form.querySelector('[data-text-mode-input]');
    const textModeButtons = [...form.querySelectorAll('[data-text-mode-switch]')];
    const brandIconModeInput = form.querySelector('[data-brand-icon-mode]');
    const removeBrandIcon = form.querySelector('[data-remove-brand-icon]');
    const brandIconButtons = [...form.querySelectorAll('[data-brand-icon-switch]')];
    const brandIconInput = form.querySelector('[data-brand-icon-input]');
    const brandIconPreview = form.querySelector('[data-brand-icon-preview]');
    const brandIconEmpty = form.querySelector('[data-brand-icon-empty]');
    const brandIconWrap = form.querySelector('[data-brand-icon-wrap]');
    const modeInput = form.querySelector('[data-background-mode]');
    const removeBackground = form.querySelector('[data-remove-background]');
    const backgroundSourceButtons = [...form.querySelectorAll('[data-background-source-switch]')];
    const backgroundLinkPanel = form.querySelector('[data-background-link-panel]');
    const backgroundInput = form.querySelector('[data-background-input]');
    const backgroundUrlInput = form.querySelector('[data-background-url-input]');
    const backgroundPreview = form.querySelector('[data-background-preview]');
    const backgroundEmpty = form.querySelector('[data-background-empty]');
    const backgroundWrap = form.querySelector('[data-background-wrap]');
    const backgroundUploadPanel = form.querySelector('[data-background-upload-panel]');
    const backgroundGrid = form.querySelector('[data-background-grid]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const initialBrandIconUrl = @json($currentBrandIconUrl);
    const initialBackgroundUrl = @json($currentBackgroundUrl);
    let richEditor = null;

    window.applyAdminFormErrors?.({
        form,
        errors: settingsFormErrors,
        fieldMap: {
            brand_icon_mode: '[data-field-target="brand_icon"]',
            background_mode: '[data-field-target="background_image"]',
            background_image_url: '[data-field-target="background_image"]',
        },
    });

    const syncOutput = () => {
        if (!richEditor || !output) return;
        const html = richEditor.getHtml().trim();
        output.value = html === '<p><br></p>' ? '' : html;
    };


    @include('admin.partials.wang-editor-helpers')

    const setTextMode = (mode) => {
        const value = mode === 'dark' ? 'dark' : 'light';
        textModeInput.value = value;
        textModeButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.textModeSwitch === value);
        });
    };

    const setBrandIconMode = (mode) => {
        const value = mode === 'custom' ? 'custom' : 'default';
        brandIconModeInput.value = value;
        brandIconButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.brandIconSwitch === value);
        });
        brandIconWrap?.classList.toggle('is-hidden', value === 'default');
    };

    const setBrandIconPreview = (src) => {
        const hasSrc = Boolean(src);
        if (brandIconPreview) {
            if (hasSrc) {
                brandIconPreview.src = src;
            }
            brandIconPreview.hidden = !hasSrc;
        }
        if (brandIconEmpty) {
            brandIconEmpty.hidden = hasSrc;
        }
    };

    const setBackgroundSource = (source) => {
        const value = source === 'default' ? 'default' : (source === 'link' ? 'link' : 'upload');
        modeInput.value = value === 'default' ? 'default' : 'custom';
        backgroundSourceButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.backgroundSourceSwitch === value);
        });
        backgroundWrap?.classList.toggle('is-hidden', value === 'default');
        backgroundGrid?.classList.toggle('is-compact', value === 'default');
        backgroundLinkPanel?.classList.toggle('is-hidden', value !== 'link');
        backgroundUploadPanel?.classList.toggle('is-hidden', value !== 'upload');
    };

    const setPreview = (src) => {
        if (!backgroundPreview || !backgroundEmpty) return;
        if (src) {
            backgroundPreview.src = src;
            backgroundPreview.hidden = false;
            backgroundEmpty.hidden = true;
        } else {
            backgroundPreview.hidden = true;
            backgroundEmpty.hidden = false;
        }
    };

    const initRichEditor = async () => {
        if (!editorHost || !toolbarHost || !output || richEditor) return;

        const wangEditor = await waitForWangEditor();
        if (!wangEditor) return;

        const { createEditor, createToolbar } = wangEditor;
        richEditor = createEditor({
            selector: '#admin-settings-editor',
            html: output.value.trim() || '<p><br></p>',
            mode: 'default',
            config: {
                hoverbarKeys: {
                    text: {
                        menuKeys: [],
                    },
                },
                onChange(editor) {
                    const html = editor.getHtml().trim();
                    output.value = html === '<p><br></p>' ? '' : html;
                },
                MENU_CONF: {
                    uploadImage: {
                        maxFileSize: 5 * 1024 * 1024,
                        allowedFileTypes: ['image/*'],
                        async customUpload(file, insertFn) {
                            const uploadedUrl = await uploadEditorImageFile({
                                file,
                                csrfToken,
                                uploadUrl: '{{ route('admin.settings.editor-images') }}',
                            });

                            insertFn(uploadedUrl, file.name, uploadedUrl);
                        },
                    },
                },
            },
        });

        createToolbar({
            editor: richEditor,
            selector: '#admin-settings-editor-toolbar',
            mode: 'default',
        });

        observeToolbarButtons(toolbarHost);
        syncOutput();
    };

    setTextMode(textModeInput?.value || 'light');
    setBrandIconMode(brandIconModeInput?.value || 'default');
    setBrandIconPreview(initialBrandIconUrl || '');
    setBackgroundSource(
        modeInput.value === 'default'
            ? 'default'
            : (Boolean(backgroundLinkPanel) && !backgroundLinkPanel.classList.contains('is-hidden') ? 'link' : 'upload')
    );
    setPreview(initialBackgroundUrl || '');

    textModeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setTextMode(button.dataset.textModeSwitch || 'light');
        });
    });

    brandIconButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const mode = button.dataset.brandIconSwitch || 'default';
            setBrandIconMode(mode);
            if (mode === 'default') {
                removeBrandIcon.value = '1';
                setBrandIconPreview('');
            } else {
                removeBrandIcon.value = '0';
                if (brandIconInput?.files?.[0]) return;
                setBrandIconPreview(initialBrandIconUrl || '');
            }
        });
    });

    backgroundSourceButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const source = button.dataset.backgroundSourceSwitch || 'default';
            setBackgroundSource(source);

            if (source === 'default') {
                removeBackground.value = '1';
                setPreview('');
                return;
            }

            removeBackground.value = '0';

            if (source === 'upload') {
                if (backgroundUrlInput) {
                    backgroundUrlInput.value = '';
                }
                if (backgroundInput?.files?.[0]) {
                    return;
                }
                setPreview(initialBackgroundUrl || '');
                return;
            }

            if (backgroundInput) {
                backgroundInput.value = '';
            }
        });
    });

    brandIconInput?.addEventListener('change', () => {
        const file = brandIconInput.files && brandIconInput.files[0];
        if (!file) return;
        removeBrandIcon.value = '0';
        setBrandIconMode('custom');
        const reader = new FileReader();
        reader.onload = (event) => setBrandIconPreview(String(event.target?.result || ''));
        reader.readAsDataURL(file);
    });

    backgroundInput?.addEventListener('change', () => {
        const file = backgroundInput.files && backgroundInput.files[0];
        if (!file) return;
        removeBackground.value = '0';
        setBackgroundSource('upload');
        if (backgroundUrlInput) {
            backgroundUrlInput.value = '';
        }
        const reader = new FileReader();
        reader.onload = (event) => setPreview(String(event.target?.result || ''));
        reader.readAsDataURL(file);
    });

    backgroundUrlInput?.addEventListener('input', () => {
        if ((backgroundUrlInput.value || '').trim() === '') return;
        removeBackground.value = '0';
        if (backgroundInput) {
            backgroundInput.value = '';
        }
        setBackgroundSource('link');
    });

    form.addEventListener('submit', () => {
        syncOutput();
        if ((backgroundUrlInput?.value || '').trim() !== '') {
            removeBackground.value = '0';
            if (backgroundInput) {
                backgroundInput.value = '';
            }
            setBackgroundSource('link');
        }
    });
    toolbarHost?.addEventListener('pointerdown', (event) => preserveSelectionOnToolbar(event, toolbarHost, richEditor), true);
    toolbarHost?.addEventListener('mousedown', (event) => preserveSelectionOnToolbar(event, toolbarHost, richEditor), true);
    toolbarHost?.addEventListener('click', (event) => handleToolbarActivation(event, toolbarHost, richEditor), true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRichEditor, { once: true });
    } else {
        initRichEditor();
    }
})();
</script>
@endpush
