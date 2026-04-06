@extends('admin.layout')

@push('head')
<link rel="stylesheet" href="{{ asset('vendor/wangeditor/style.css') }}?v={{ @filemtime(public_path('vendor/wangeditor/style.css')) ?: time() }}">
<style>
    .admin-product-editor-page {
        display: grid;
        gap: 1.25rem;
    }

    .admin-product-editor-top {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .admin-product-editor-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.5rem;
        padding: 0 0.95rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.86);
        color: var(--ink);
        text-decoration: none;
        font-weight: 700;
    }

    .admin-product-editor-back:hover {
        border-color: rgba(204, 106, 71, 0.22);
        color: var(--accent-deep);
    }

    .admin-editor-categories {
        display: grid;
        gap: 1rem;
    }

    .admin-editor-categories-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .admin-editor-categories-head h2,
    .admin-editor-current strong,
    .admin-editor-input-shell label,
    .admin-editor-upload-label {
        margin: 0;
    }

    .admin-editor-categories-head h2 {
        font-size: clamp(1.25rem, 2vw, 1.7rem);
    }

    .admin-editor-category-form {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: min(100%, 22rem);
    }

    .admin-editor-category-form input,
    .admin-editor-input,
    .admin-editor-toolbar select {
        width: 100%;
        min-height: 2.85rem;
        padding: 0 1rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.96);
        color: var(--ink);
        font: inherit;
    }

    .admin-editor-category-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.2rem;
    }

    .admin-editor-category-tab {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 0.7rem;
        min-height: 2.85rem;
        padding: 0 1rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 999rem;
        background: rgba(255, 255, 255, 0.82);
        color: var(--ink);
        text-decoration: none;
        white-space: nowrap;
    }

    .admin-editor-category-tab strong {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.65rem;
        height: 1.65rem;
        padding: 0 0.45rem;
        border-radius: 999rem;
        background: rgba(27, 36, 48, 0.07);
        font-size: 0.8rem;
        font-weight: 800;
    }

    .admin-editor-category-tab.is-active {
        border-color: rgba(204, 106, 71, 0.22);
        background: rgba(204, 106, 71, 0.12);
        color: var(--accent-deep);
    }

    .admin-editor-category-tab.is-active strong {
        background: rgba(255, 255, 255, 0.78);
        color: var(--accent-deep);
    }

    .admin-editor-current {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 1.15rem;
        background: rgba(246, 249, 253, 0.82);
    }

    .admin-editor-current strong {
        font-size: 1rem;
    }

    .admin-editor-layout {
        display: grid;
        gap: 1.45rem;
    }

    .admin-editor-input-card {
        display: grid;
        gap: 1.2rem;
    }

    .admin-detail-tags-shell {
        display: grid;
        grid-template-columns: minmax(16rem, 18rem) minmax(0, 1fr);
        gap: 1.2rem;
        margin: 2rem 0 2rem;
    }

    .admin-detail-tags-aside,
    .admin-detail-tags-main {
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.92);
        padding: 1.1rem;
    }

    .admin-detail-tags-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .admin-detail-tags-head h2,
    .admin-detail-tags-aside h2,
    .admin-detail-tag-group h3,
    .admin-detail-tag-empty strong {
        margin: 0;
    }

    .admin-detail-tags-head p,
    .admin-detail-tags-aside p,
    .admin-detail-tag-group p,
    .admin-detail-tag-empty p,
    .admin-detail-tag-note li {
        color: var(--muted);
    }

    .admin-detail-tags-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.25rem;
        padding: 0 0.9rem;
        border-radius: 0.9rem;
        background: rgba(88, 114, 255, 0.08);
        border: 1px solid rgba(88, 114, 255, 0.12);
        color: #4a5eff;
        font-size: 0.9rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .admin-detail-style-list {
        display: grid;
        gap: 0.7rem;
        margin-top: 1rem;
    }

    .admin-detail-style-option {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.8rem;
        padding: 0 1rem;
        border-radius: 0.95rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        background: rgba(248, 250, 252, 0.92);
        color: var(--ink);
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
    }

    .admin-detail-style-option.is-active {
        background: #1e293b;
        border-color: #1e293b;
        color: #fff;
        box-shadow: 0 0.75rem 1.6rem rgba(15, 23, 42, 0.16);
    }

    .admin-detail-style-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.65rem;
    }

    .admin-detail-tag-note {
        margin: 1.2rem 0 0;
        padding: 1rem 0 0;
        border-top: 1px solid rgba(27, 36, 48, 0.08);
        display: grid;
        gap: 0.5rem;
    }

    .admin-detail-tag-note ol {
        margin: 0;
        padding-left: 1.05rem;
        display: grid;
        gap: 0.45rem;
    }

    .admin-detail-tag-selected {
        min-height: 5.8rem;
        padding: 0.8rem;
        border: 1px dashed rgba(148, 163, 184, 0.32);
        border-radius: 1rem;
        background: rgba(248, 250, 252, 0.78);
        display: flex;
        flex-wrap: wrap;
        align-content: flex-start;
        gap: 0.75rem;
    }

    .admin-detail-tag-empty {
        width: 100%;
        min-height: 4rem;
        display: grid;
        place-items: center;
        text-align: center;
        gap: 0.35rem;
    }

    .admin-detail-tag-groups {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(27, 36, 48, 0.08);
    }

    .admin-detail-tag-picker,
    .admin-detail-tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 2.4rem;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        border: 1px solid rgba(93, 130, 255, 0.22);
        background: rgba(255, 255, 255, 0.96);
        font: inherit;
        font-size: 0.88rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .admin-detail-tag-picker {
        cursor: pointer;
    }

    .admin-detail-tag-picker:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .admin-detail-tag-chip {
        padding-right: 0.45rem;
        cursor: grab;
        box-shadow: 0 0.45rem 0.9rem rgba(15, 23, 42, 0.06);
    }

    .admin-detail-tag-chip.is-dragover {
        transform: translateY(-1px);
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.16);
    }

    .admin-detail-tag-chip.is-dragging {
        opacity: 0.42;
    }

    .admin-detail-tag-handle {
        color: rgba(100, 116, 139, 0.85);
        user-select: none;
    }

    .admin-detail-tag-text {
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
        padding: 0.32rem 0.65rem;
        border-radius: 999px;
        border: 1px solid currentColor;
        cursor: text;
    }

    .admin-detail-tag-text input {
        width: 5.8rem;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        text-align: center;
        outline: none;
    }

    .admin-detail-tag-remove {
        width: 1.65rem;
        height: 1.65rem;
        border: 0;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
        color: rgba(100, 116, 139, 0.92);
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .admin-detail-tag-remove:hover {
        background: #ef4444;
        color: #fff;
    }

    .admin-detail-tag--blue { color: #4f7bff; background: rgba(79, 123, 255, 0.08); }
    .admin-detail-tag--violet { color: #7c5cff; background: rgba(124, 92, 255, 0.08); }
    .admin-detail-tag--sky { color: #2196f3; background: rgba(33, 150, 243, 0.08); }
    .admin-detail-tag--slate { color: #7a879d; background: rgba(122, 135, 157, 0.1); }
    .admin-detail-tag--green { color: #18b87a; background: rgba(24, 184, 122, 0.1); }
    .admin-detail-tag--mint { color: #1fb89b; background: rgba(31, 184, 155, 0.1); }
    .admin-detail-tag--emerald { color: #13a86f; background: rgba(19, 168, 111, 0.1); }
    .admin-detail-tag--lime { color: #73b62d; background: rgba(115, 182, 45, 0.1); }

    .admin-editor-grid {
        display: grid;
        grid-template-columns: minmax(12rem, 10.5rem) minmax(0, 1fr);
        gap: 1.15rem;
        align-items: start;
    }

    .admin-editor-upload-wrap {
        display: grid;
        gap: 0.75rem;
    }

    .admin-editor-upload-row {
        display: flex;
        align-items: flex-end;
        gap: 0.85rem;
    }

    .admin-editor-upload {
        position: relative;
        aspect-ratio: 1 / 1;
        width: 100%;
        border: 1px dashed rgba(27, 36, 48, 0.16);
        border-radius: 1.25rem;
        background: rgba(246, 249, 253, 0.9);
        overflow: hidden;
        cursor: pointer;
        transition: border-color 0.18s ease, background-color 0.18s ease;
    }

    .admin-editor-upload.is-dragover {
        border-color: rgba(204, 106, 71, 0.38);
        background: rgba(255, 247, 243, 0.96);
    }

    .admin-editor-upload input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .admin-editor-upload img {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .admin-editor-upload-empty {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        padding: 1rem;
        text-align: center;
        color: var(--muted);
        z-index: 2;
    }

    .admin-editor-upload-empty[hidden],
    .admin-editor-upload-overlay[hidden],
    .admin-editor-upload img[hidden] {
        display: none !important;
    }

    .admin-editor-upload-empty strong {
        display: block;
        font-size: 2rem;
        color: var(--accent);
        line-height: 1;
    }

    .admin-editor-upload-empty span,
    .admin-editor-upload-note,
    .admin-editor-toolbar-note {
        display: block;
        color: var(--muted);
    }

    .admin-editor-upload-overlay {
        position: absolute;
        right: 0.7rem;
        bottom: 0.7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0 0.85rem;
        border-radius: 999rem;
        background: rgba(27, 36, 48, 0.72);
        color: #fff;
        font-size: 0.86rem;
        font-weight: 700;
        pointer-events: none;
        z-index: 3;
    }

    .admin-editor-upload-note,
    .admin-editor-toolbar-note {
        line-height: 1.6;
    }

    .admin-editor-upload-actions {
        display: flex;
        gap: 0.65rem;
    }

    .admin-editor-button-light {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.65rem;
        padding: 0 0.95rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.88);
        color: var(--ink);
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .admin-editor-button-light:hover {
        border-color: rgba(204, 106, 71, 0.24);
        color: var(--accent-deep);
    }

    .admin-editor-upload-clear {
        flex: 0 0 auto;
        width: 3rem;
        min-width: 3rem;
        min-height: 3rem;
        padding: 0;
        border-radius: 1rem;
    }

    .admin-editor-upload-clear svg {
        width: 1rem;
        height: 1rem;
    }

    .admin-editor-fields {
        display: grid;
        gap: 1.15rem;
    }

    .admin-editor-input-shell {
        display: grid;
        gap: 0.45rem;
    }

    .admin-editor-input-shell label {
        font-size: 0.92rem;
        font-weight: 700;
    }

    .admin-editor-input--readonly,
    .admin-editor-input--readonly:disabled {
        border-color: rgba(10, 132, 255, 0.18);
        background: rgba(10, 132, 255, 0.08);
        color: #245a9a;
        box-shadow: none;
        opacity: 1;
        cursor: default;
    }

    .admin-editor-input--readonly:focus,
    .admin-editor-input--readonly:disabled:focus {
        outline: none;
        border-color: rgba(10, 132, 255, 0.18);
        box-shadow: none;
    }

    .admin-editor-toolbar-shell {
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 1.25rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.92);
    }

    .admin-editor-toolbar-head {
        padding: 1rem 1rem 0.85rem;
        border-bottom: 1px solid rgba(27, 36, 48, 0.08);
    }

    .admin-editor-toolbar-head h2 {
        margin: 0;
        font-size: 1rem;
    }

    .admin-editor-wang {
        overflow: hidden;
        border-top: 1px solid rgba(27, 36, 48, 0.08);
        background: rgba(255, 255, 255, 0.94);
    }

    .admin-editor-wang-toolbar .w-e-toolbar {
        border: 0 !important;
        background: rgba(246, 249, 252, 0.88) !important;
        border-bottom: 1px solid rgba(27, 36, 48, 0.08) !important;
    }

    .admin-editor-wang-surface {
        min-height: 22rem;
    }

    .admin-editor-wang-surface .w-e-text-container {
        min-height: 22rem !important;
        background: rgba(255, 255, 255, 0.96) !important;
    }

    .admin-editor-wang-surface .w-e-scroll {
        min-height: 22rem !important;
    }

    .admin-editor-wang-surface .w-e-text-placeholder {
        top: 1rem !important;
        color: rgba(100, 116, 139, 0.92) !important;
    }

    .admin-editor-wang-surface [data-slate-editor] {
        min-height: 22rem !important;
        padding: 1rem !important;
        line-height: 1.85 !important;
    }

    .admin-editor-wang-surface img {
        max-width: 100%;
        border-radius: 0.85rem;
    }

    .admin-editor-wang-toolbar button,
    .admin-editor-wang-toolbar [role='button'],
    .admin-editor-wang-toolbar .w-e-bar-item {
        cursor: pointer !important;
    }

    .admin-editor-submit {
        width: 100%;
        min-height: 3.25rem;
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, #ff8846, #ff5b4e);
        color: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    @media (max-width: 62rem) {
        .admin-editor-categories-head,
        .admin-editor-current,
        .admin-product-editor-top {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-editor-category-form {
            width: 100%;
        }

        .admin-detail-tags-shell {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 48rem) {
        .admin-editor-grid {
            grid-template-columns: 1fr;
        }

        .admin-editor-upload {
            max-width: 12rem;
        }

        .admin-editor-category-form,
        .admin-editor-submit {
            width: 100%;
        }

        .admin-editor-upload-row {
            align-items: stretch;
        }

        .admin-detail-style-row {
            grid-template-columns: 1fr;
        }
    }
</style>
<style>
.admin-editor-toolbar-shell,
.admin-editor-wang,
.admin-editor-wang-toolbar,
.admin-editor-wang-toolbar .w-e-toolbar,
.admin-editor-wang-toolbar .w-e-bar,
.admin-editor-wang-toolbar .w-e-bar-item-group {
    overflow: visible !important;
}

.admin-editor-toolbar-shell,
.admin-editor-wang {
    position: relative;
}

.admin-editor-wang-toolbar {
    position: relative;
    z-index: 40;
}

.admin-editor-wang-surface {
    position: relative;
    z-index: 1;
}

.admin-editor-wang-toolbar .w-e-drop-panel,
.admin-editor-wang-toolbar .w-e-select-list,
.admin-editor-wang-toolbar .w-e-modal,
.admin-editor-wang-toolbar .w-e-panel-container,
.admin-editor-wang-toolbar .w-e-menu-tooltip-v5,
.admin-editor-wang .w-e-hover-bar-container {
    z-index: 60 !important;
}
</style>
@endpush

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $formAction = $isEdit ? route('admin.products.update', $productRecord) : route('admin.products.store');
        $backCategory = old('category_id', $returnCategory ?: $selectedCategoryId);
        $rawImagePath = $productRecord?->image_path;
        $imagePreview = $rawImagePath
            ? ((str_starts_with($rawImagePath, 'http://') || str_starts_with($rawImagePath, 'https://')) ? $rawImagePath : asset($rawImagePath))
            : null;
        $initialDetailTagStyle = old('detail_tag_style', $productRecord?->detail_tag_style ?: 'glass');
        $initialDetailTags = old(
            'detail_tags',
            json_encode($productRecord?->detail_tags ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $categoryRoute = function (string $categoryId) use ($isEdit, $productRecord) {
            return $isEdit
                ? route('admin.products.edit', ['product' => $productRecord, 'category' => $categoryId])
                : route('admin.products.create', ['category' => $categoryId]);
        };
    @endphp

    <div class="admin-product-editor-page">
        <div class="admin-product-editor-top">
            <a href="{{ route('admin.products', ['category' => $backCategory]) }}" class="admin-product-editor-back">返回</a>
        </div>

        @unless ($isEdit)
            <section class="admin-card admin-editor-categories">
                <div class="admin-editor-categories-head">
                    <h2>{{ $selectedCategoryName }}</h2>

                    <form class="admin-editor-category-form" action="{{ route('admin.products.categories.store') }}" method="POST" data-no-spa>
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $isEdit ? 'edit' : 'create' }}">
                        @if ($isEdit && $productRecord)
                            <input type="hidden" name="product_id" value="{{ $productRecord->id }}">
                        @endif
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="输入新分类名称" maxlength="30" required>
                        <button type="submit" class="admin-button">新增分类</button>
                    </form>
                </div>

                <div class="admin-editor-category-tabs">
                    @foreach ($categories as $category)
                        <a href="{{ $categoryRoute($category['id']) }}" class="admin-editor-category-tab {{ $selectedCategoryId === $category['id'] ? 'is-active' : '' }}">
                            <span>{{ $category['name'] }}</span>
                            <strong>{{ $category['product_count'] ?? 0 }}</strong>
                        </a>
                    @endforeach
                </div>

                <div class="admin-editor-current" data-field-target="category_id">
                    <strong>{{ $selectedCategoryName }}</strong>
                </div>
            </section>
        @endunless

        <section class="admin-card admin-editor-layout">
            @if (session('product_notice'))
            @endif

            <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" data-rich-form data-no-spa>
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <input type="hidden" name="category_id" value="{{ old('category_id', $selectedCategoryId) }}">
                <input type="hidden" name="remove_image" value="0" data-remove-image>
                <textarea name="description_html" hidden data-editor-output>{{ old('description_html', \App\Support\RichTextSanitizer::sanitize((string) $productRecord?->description_html)) }}</textarea>
                <textarea name="detail_tags" hidden data-detail-tags-output>{{ $initialDetailTags }}</textarea>
                <input type="hidden" name="detail_tag_style" value="{{ $initialDetailTagStyle }}" data-detail-tag-style-output>

                <div class="admin-editor-input-card">
                    @if ($isEdit)
                        <div class="admin-editor-input-shell" data-field-target="category_id">
                            <label for="product_category_name">所属商品分类</label>
                            <input id="product_category_name" class="admin-editor-input admin-editor-input--readonly" type="text" value="{{ $selectedCategoryName }}" disabled>
                        </div>
                    @endif

                    <div class="admin-editor-grid">
                        <div class="admin-editor-upload-wrap" data-field-target="image">
                            <p class="admin-editor-upload-label">商品图片</p>
                            <div class="admin-editor-upload-row">
                                <label class="admin-editor-upload" data-image-dropzone>
                                    <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp,.gif,.avif" data-image-input>
                                    <img src="{{ $imagePreview ?: asset('product-placeholder.svg') }}" alt="商品图片预览" data-image-preview @if(! $imagePreview) hidden @endif>
                                    <div class="admin-editor-upload-empty" data-image-empty @if($imagePreview) hidden @endif><div><strong>+</strong></div></div>







                                </label>
                                <button type="button" class="admin-editor-button-light admin-editor-upload-clear" data-image-clear aria-label="清空图片" title="清空图片">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 4.5h6l.55 1.5H19a1 1 0 1 1 0 2h-1l-.62 9.26A2.5 2.5 0 0 1 14.89 19.5H9.11a2.5 2.5 0 0 1-2.49-2.24L6 8H5a1 1 0 1 1 0-2h3.45L9 4.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                        <path d="M10 10.5v4.5M14 10.5v4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                        </div>

                        <div class="admin-editor-fields">
                            <div class="admin-editor-input-shell" data-field-target="name">
                                <label for="product_name">商品名称</label>
                                <input id="product_name" class="admin-editor-input" type="text" name="name" value="{{ old('name', $productRecord?->name) }}" maxlength="80" required>
                            </div>

                            <div class="admin-editor-input-shell" data-field-target="compare_price">
                                <label for="product_compare_price">划线价</label>
                                <input id="product_compare_price" class="admin-editor-input" type="number" name="compare_price" value="{{ old('compare_price', $productRecord?->compare_price) }}" min="0" step="0.01" inputmode="decimal">
                            </div>

                            <div class="admin-editor-input-shell" data-field-target="price">
                                <label for="product_price">售价</label>
                                <input id="product_price" class="admin-editor-input" type="number" name="price" value="{{ old('price', $productRecord?->price) }}" min="0" step="0.01" inputmode="decimal" required>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="admin-detail-tags-shell" data-detail-tags-editor data-field-target="detail_tags">
                    <aside class="admin-detail-tags-aside">
                        <h2>标签视觉风格</h2>

                        <div class="admin-detail-style-list">
                            <div class="admin-detail-style-row">
                                <button type="button" class="admin-detail-style-option" data-style-select="glass">磨砂玻璃</button>
                                <button type="button" class="admin-detail-style-option" data-style-select="minimal">极简白底</button>
                                <button type="button" class="admin-detail-style-option" data-style-select="gradient">高亮渐变</button>
                            </div>
                        </div>
                    </aside>

                    <section class="admin-detail-tags-main">
                        <div class="admin-detail-tags-head">
                            <div>
                                <h2>详情卡标签</h2>
                            </div>
                            <span class="admin-detail-tags-count" data-detail-tags-count>已选 0 / 8</span>
                        </div>

                        <div class="admin-detail-tag-selected" data-selected-tags>
                            <div class="admin-detail-tag-empty" data-selected-tags-empty>
                                <strong>请从下方待选标签中点击添加</strong>
                            </div>
                        </div>

                        <div class="admin-detail-tag-groups" data-available-tag-groups></div>
                    </section>
                </section>

                <section class="admin-editor-toolbar-shell" data-rich-editor-root data-field-target="description_html">
                    <div class="admin-editor-toolbar-head">
                        <h2>商品描述</h2>

                    </div>

                    <div class="admin-editor-wang">
                        <div class="admin-editor-wang-toolbar" id="admin-product-editor-toolbar"></div>
                        <div class="admin-editor-wang-surface" id="admin-product-editor"></div>
                    </div>
                </section>

                <div style="margin-top: 1.35rem;">
                    <button type="submit" class="admin-editor-submit">{{ $isEdit ? '保存商品' : '创建商品' }}</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/wangeditor/index.js') }}?v={{ @filemtime(public_path('vendor/wangeditor/index.js')) ?: time() }}"></script>
<script>
(() => {
    const form = document.querySelector('[data-rich-form]');
    if (!form) {
        return;
    }

    const productFormErrors = @json($errors->getMessages());

    const editorHost = document.getElementById('admin-product-editor');
    const toolbarHost = document.getElementById('admin-product-editor-toolbar');
    const output = form.querySelector('[data-editor-output]');
    const comparePriceInput = form.querySelector('#product_compare_price');
    const priceInput = form.querySelector('#product_price');
    const imageInput = form.querySelector('[data-image-input]');
    const imagePreview = form.querySelector('[data-image-preview]');
    const imageEmpty = form.querySelector('[data-image-empty]');
    const imageOverlay = form.querySelector('[data-image-overlay]');
    const imageClear = form.querySelector('[data-image-clear]');
    const removeImage = form.querySelector('[data-remove-image]');
    const imageDropzone = form.querySelector('[data-image-dropzone]');
    const detailTagsOutput = form.querySelector('[data-detail-tags-output]');
    const detailTagStyleOutput = form.querySelector('[data-detail-tag-style-output]');
    const detailTagsEditor = form.querySelector('[data-detail-tags-editor]');
    const detailTagsSelected = detailTagsEditor?.querySelector('[data-selected-tags]');
    const detailTagsEmpty = detailTagsEditor?.querySelector('[data-selected-tags-empty]');
    const detailTagsGroups = detailTagsEditor?.querySelector('[data-available-tag-groups]');
    const detailTagsCount = detailTagsEditor?.querySelector('[data-detail-tags-count]');
    const detailTagStyleButtons = Array.from(detailTagsEditor?.querySelectorAll('[data-style-select]') || []);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const defaultImage = imagePreview?.getAttribute('src') || '';
    const hasInitialImage = imagePreview && !imagePreview.hasAttribute('hidden');
    const maxDetailTags = 8;
    let dragDepth = 0;
    let richEditor = null;
    let draggedDetailTagIndex = null;
    let selectedDetailTags = [];
    let currentDetailTagStyle = 'glass';

    window.applyAdminFormErrors?.({
        form,
        errors: productFormErrors,
        fieldMap: {
            category_id: '[data-field-target="category_id"]',
            detail_tag_style: '[data-detail-tags-editor]',
            detail_tags: '[data-detail-tags-editor]',
        },
    });

    const detailTagLibrary = [
        {
            title: '',
            hint: '',
            items: [
                { key: 'quick-start', icon: '⚡', text: '3分钟极速搞定', tone: 'blue' },
                { key: 'remote-help', icon: '💻', text: '远程1对1代操', tone: 'sky' },
                { key: 'one-click', icon: '🚀', text: '免翻一键直连', tone: 'violet' },
                { key: 'hands-free', icon: '🪐', text: '全程无需动手', tone: 'slate' },
            ],
        },
        {
            title: '',
            hint: '',
            items: [
                { key: 'native-safe', icon: '🛡️', text: '原生环境防封', tone: 'green' },
                { key: 'refund-fast', icon: '💰', text: '不成功秒退款', tone: 'mint' },
                { key: 'independent-account', icon: '🔒', text: '独享独立账号', tone: 'emerald' },
                { key: 'after-sale', icon: '🤝', text: '售后随时找人', tone: 'lime' },
                { key: 'keep-updated', icon: '📗', text: '包更新不失联', tone: 'green' },
            ],
        },
    ];

    const normalizeClientDetailTags = (value) => {
        const allowedTones = new Set(['blue', 'violet', 'sky', 'slate', 'green', 'mint', 'emerald', 'lime']);
        const source = Array.isArray(value) ? value : [];

        return source
            .filter((item) => item && typeof item === 'object')
            .map((item, index) => {
                const text = String(item.text || '').replace(/\s+/g, ' ').trim().slice(0, 18);
                const icon = String(item.icon || '').trim().slice(0, 4);
                const tone = String(item.tone || 'blue').trim();

                if (!text || !icon) {
                    return null;
                }

                return {
                    uid: String(item.uid || `${item.key || 'tag'}-${index}`),
                    key: String(item.key || ''),
                    text,
                    icon,
                    tone: allowedTones.has(tone) ? tone : 'blue',
                };
            })
            .filter(Boolean)
            .slice(0, maxDetailTags);
    };

    const syncDetailTagOutputs = () => {
        if (detailTagsOutput) {
            detailTagsOutput.value = JSON.stringify(
                selectedDetailTags.map(({ key, text, icon, tone }) => ({ key, text, icon, tone })),
            );
        }

        if (detailTagStyleOutput) {
            detailTagStyleOutput.value = currentDetailTagStyle;
        }
    };

    const renderDetailTagCount = () => {
        if (!detailTagsCount) {
            return;
        }

        detailTagsCount.textContent = `已选 ${selectedDetailTags.length} / ${maxDetailTags}`;
    };

    const renderDetailTagStyles = () => {
        detailTagStyleButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.styleSelect === currentDetailTagStyle);
        });
    };

    const finishDetailTagEdit = (input, index) => {
        const nextValue = input.value.replace(/\s+/g, ' ').trim().slice(0, 18);
        if (nextValue) {
            selectedDetailTags[index].text = nextValue;
        }
        renderSelectedDetailTags();
    };

    const startDetailTagEdit = (button, index) => {
        if (!selectedDetailTags[index]) {
            return;
        }

        const current = selectedDetailTags[index];
        button.innerHTML = '';

        const icon = document.createElement('span');
        icon.textContent = current.icon;

        const input = document.createElement('input');
        input.type = 'text';
        input.value = current.text;
        input.maxLength = 18;

        const stop = (event) => event.stopPropagation();
        input.addEventListener('click', stop);
        input.addEventListener('mousedown', stop);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                input.blur();
            }
        });
        input.addEventListener('blur', () => finishDetailTagEdit(input, index), { once: true });

        button.append(icon, input);
        input.focus();
        input.select();
    };

    const renderSelectedDetailTags = () => {
        if (!detailTagsSelected) {
            return;
        }

        detailTagsSelected.querySelectorAll('[data-selected-tag-chip]').forEach((node) => node.remove());
        if (detailTagsEmpty) {
            detailTagsEmpty.hidden = selectedDetailTags.length > 0;
            detailTagsEmpty.style.display = selectedDetailTags.length > 0 ? 'none' : 'grid';
        }

        selectedDetailTags.forEach((tag, index) => {
            const chip = document.createElement('div');
            chip.className = `admin-detail-tag-chip admin-detail-tag--${tag.tone}`;
            chip.setAttribute('data-selected-tag-chip', 'true');
            chip.draggable = true;

            chip.addEventListener('dragstart', (event) => {
                draggedDetailTagIndex = index;
                event.dataTransfer.effectAllowed = 'move';
                chip.classList.add('is-dragging');
            });
            chip.addEventListener('dragover', (event) => {
                event.preventDefault();
                chip.classList.add('is-dragover');
            });
            chip.addEventListener('dragleave', () => chip.classList.remove('is-dragover'));
            chip.addEventListener('drop', (event) => {
                event.preventDefault();
                chip.classList.remove('is-dragover');
                if (draggedDetailTagIndex === null || draggedDetailTagIndex === index) {
                    return;
                }
                const moved = selectedDetailTags.splice(draggedDetailTagIndex, 1)[0];
                selectedDetailTags.splice(index, 0, moved);
                draggedDetailTagIndex = null;
                renderDetailTagEditor();
            });
            chip.addEventListener('dragend', () => {
                draggedDetailTagIndex = null;
                chip.classList.remove('is-dragging', 'is-dragover');
            });

            const handle = document.createElement('span');
            handle.className = 'admin-detail-tag-handle';
            handle.textContent = '≡';

            const text = document.createElement('button');
            text.type = 'button';
            text.className = 'admin-detail-tag-text';
            text.innerHTML = `<span>${tag.icon}</span><span>${tag.text}</span>`;
            text.addEventListener('click', () => startDetailTagEdit(text, index));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'admin-detail-tag-remove';
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selectedDetailTags.splice(index, 1);
                renderDetailTagEditor();
            });

            chip.append(handle, text, remove);
            detailTagsSelected.append(chip);
        });

        syncDetailTagOutputs();
        renderDetailTagCount();
    };

    const renderAvailableDetailTagGroups = () => {
        if (!detailTagsGroups) {
            return;
        }

        detailTagsGroups.innerHTML = '';

        detailTagLibrary.forEach((group) => {
            group.items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `admin-detail-tag-picker admin-detail-tag--${item.tone}`;
                button.innerHTML = `<span>${item.icon}</span><span>${item.text}</span><span>+</span>`;
                button.disabled = selectedDetailTags.length >= maxDetailTags;
                button.addEventListener('click', () => {
                    if (button.disabled) {
                        return;
                    }
                    selectedDetailTags.push({
                        uid: `${item.key}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                        key: item.key,
                        text: item.text,
                        icon: item.icon,
                        tone: item.tone,
                    });
                    renderDetailTagEditor();
                });
                detailTagsGroups.append(button);
            });
        });
    };

    const renderDetailTagEditor = () => {
        renderDetailTagStyles();
        renderSelectedDetailTags();
        renderAvailableDetailTagGroups();
    };

    const initDetailTagEditor = () => {
        if (!detailTagsEditor) {
            return;
        }

        try {
            selectedDetailTags = normalizeClientDetailTags(JSON.parse(detailTagsOutput?.value || '[]'));
        } catch {
            selectedDetailTags = [];
        }

        currentDetailTagStyle = ['glass', 'minimal', 'gradient'].includes(detailTagStyleOutput?.value || '')
            ? detailTagStyleOutput.value
            : 'glass';

        detailTagStyleButtons.forEach((button) => {
            button.addEventListener('click', () => {
                currentDetailTagStyle = button.dataset.styleSelect || 'glass';
                renderDetailTagEditor();
            });
        });

        renderDetailTagEditor();
    };

    const setPreviewState = (src, visible) => {
        if (!imagePreview || !imageEmpty) {
            return;
        }

        if (visible && src) {
            imagePreview.src = src;
            imagePreview.hidden = false;
            imageEmpty.hidden = true;
            if (imageOverlay) {
                imageOverlay.hidden = false;
            }
        } else {
            imagePreview.hidden = true;
            imageEmpty.hidden = false;
            if (imageOverlay) {
                imageOverlay.hidden = true;
            }
        }
    };

    const previewFile = (file) => {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        removeImage.value = '0';
        const reader = new FileReader();
        reader.onload = (event) => {
            setPreviewState(String(event.target?.result || ''), true);
        };
        reader.readAsDataURL(file);
    };

    const assignFile = (file) => {
        if (!imageInput || !file || !file.type.startsWith('image/')) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        imageInput.files = transfer.files;
        previewFile(file);
    };

    const syncOutput = () => {
        if (!richEditor || !output) {
            return;
        }

        const html = richEditor.getHtml().trim();
        output.value = html === '<p><br></p>' ? '' : html;
    };

    const validateComparePrice = () => {
        if (!(comparePriceInput instanceof HTMLInputElement) || !(priceInput instanceof HTMLInputElement)) {
            return true;
        }

        const compareValue = comparePriceInput.value.trim();
        const priceValue = priceInput.value.trim();

        if (compareValue === '' || priceValue === '') {
            comparePriceInput.setCustomValidity('');
            return true;
        }

        const compareNumber = Number(compareValue);
        const priceNumber = Number(priceValue);
        const isValid = Number.isFinite(compareNumber)
            && Number.isFinite(priceNumber)
            && compareNumber >= priceNumber;

        comparePriceInput.setCustomValidity(isValid ? '' : '划线价不能小于售价。');

        return isValid;
    };


    @include('admin.partials.wang-editor-helpers')

    const initRichEditor = () => {
        if (!window.wangEditor || !editorHost || !toolbarHost || !output) {
            return;
        }

        const { createEditor, createToolbar } = window.wangEditor;
        richEditor = createEditor({
            selector: '#admin-product-editor',
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
                                uploadUrl: '{{ route('admin.editor.images') }}',
                            });

                            insertFn(uploadedUrl, file.name, uploadedUrl);
                        },
                    },
                },
            },
        });

        createToolbar({
            editor: richEditor,
            selector: '#admin-product-editor-toolbar',
            mode: 'default',
        });

        observeToolbarButtons(toolbarHost);
        syncOutput();
    };

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files && imageInput.files[0];
            if (!file) {
                if (!hasInitialImage) {
                    setPreviewState('', false);
                }
                return;
            }

            previewFile(file);
        });
    }

    if (imageClear) {
        imageClear.addEventListener('click', () => {
            if (imageInput) {
                imageInput.value = '';
            }
            removeImage.value = '1';
            setPreviewState(defaultImage, false);
        });
    }

    if (imageDropzone) {
        imageDropzone.addEventListener('dragenter', (event) => {
            event.preventDefault();
            dragDepth += 1;
            imageDropzone.classList.add('is-dragover');
        });

        imageDropzone.addEventListener('dragover', (event) => {
            event.preventDefault();
            imageDropzone.classList.add('is-dragover');
        });

        imageDropzone.addEventListener('dragleave', (event) => {
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (dragDepth === 0) {
                imageDropzone.classList.remove('is-dragover');
            }
        });

        imageDropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            dragDepth = 0;
            imageDropzone.classList.remove('is-dragover');
            const file = event.dataTransfer?.files?.[0];
            if (file) {
                assignFile(file);
            }
        });
    }

    comparePriceInput?.addEventListener('input', validateComparePrice);
    comparePriceInput?.addEventListener('change', validateComparePrice);
    priceInput?.addEventListener('input', validateComparePrice);
    priceInput?.addEventListener('change', validateComparePrice);

    form.addEventListener('submit', (event) => {
        syncOutput();

        if (validateComparePrice()) {
            return;
        }

        event.preventDefault();
        window.applyAdminFormErrors?.({
            form,
            errors: {
                compare_price: ['划线价不能小于售价。'],
            },
            showDialog: true,
        });
    });
    toolbarHost?.addEventListener('pointerdown', (event) => preserveSelectionOnToolbar(event, toolbarHost, richEditor), true);
    toolbarHost?.addEventListener('mousedown', (event) => preserveSelectionOnToolbar(event, toolbarHost, richEditor), true);
    toolbarHost?.addEventListener('click', (event) => handleToolbarActivation(event, toolbarHost, richEditor), true);
    initDetailTagEditor();
    initRichEditor();
})();
</script>
@endpush
