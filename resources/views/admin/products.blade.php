@extends('admin.layout')

@push('head')
<style>
    .admin-category-tabs-drag {
        display: flex;
        flex-wrap: nowrap;
        gap: clamp(0.7rem, 1vw, 1rem);
        overflow-x: auto;
        padding: 0.18rem 0.05rem 0.35rem;
        margin: -0.18rem -0.05rem 0;
        scrollbar-width: thin;
    }

    .admin-category-tab {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 0.85rem;
        min-height: 3rem;
        padding: 0 1.05rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 999rem;
        background: rgba(255, 255, 255, 0.82);
        color: var(--ink);
        text-decoration: none;
        white-space: nowrap;
        will-change: transform;
        transition:
            transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
            box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1),
            border-color 0.22s ease,
            background-color 0.22s ease,
            opacity 0.18s ease;
    }

    .admin-category-tab.is-active {
        border-color: rgba(204, 106, 71, 0.24);
        background: rgba(204, 106, 71, 0.12);
        color: var(--accent-deep);
    }

    .admin-category-tab strong {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.65rem;
        height: 1.65rem;
        padding: 0 0.45rem;
        border-radius: 999rem;
        background: rgba(27, 36, 48, 0.07);
        color: var(--ink);
        font-size: 0.8rem;
        font-weight: 800;
    }

    .admin-category-tab.is-active strong {
        background: rgba(255, 255, 255, 0.78);
        color: var(--accent-deep);
    }

    .admin-category-tab.is-draggable {
        cursor: grab;
        user-select: none;
        touch-action: pan-x;
    }

    .admin-category-tab.is-draggable:active {
        cursor: grabbing;
    }

    .admin-category-tab.is-ghost,
    .admin-products-table tbody tr.is-ghost {
        opacity: 0.34;
    }

    .admin-category-tab.is-chosen,
    .admin-products-table tbody tr.is-chosen {
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
    }

    .admin-category-tab.is-dragging,
    .admin-products-table tbody tr.is-dragging {
        opacity: 0.94;
    }

    .admin-category-tab-handle,
    .admin-row-handle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--muted);
        letter-spacing: 0.12em;
        user-select: none;
    }

    .admin-category-tab-handle {
        min-width: 1.4rem;
        font-size: 0.82rem;
    }

    .admin-products-sort-hint {
        margin: 0.35rem 0 0;
        color: var(--muted);
        line-height: 1.7;
    }

    .admin-category-manage-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 1.15rem;
        background: rgba(246, 249, 253, 0.82);
    }

    .admin-category-manage-bar strong {
        font-size: 1rem;
    }

    .admin-category-manage-label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-sort-hint-inline,
    .admin-sort-hint-th {
        color: var(--muted);
        font-size: 0.82rem;
        font-weight: 600;
    }

    .admin-sort-hint-th {
        margin-left: 0.5rem;
    }

    .admin-category-manage-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .admin-category-rename-form {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .admin-category-rename-form input {
        min-width: min(20rem, 55vw);
        min-height: 2.85rem;
        padding: 0 1rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.96);
        color: var(--ink);
        font: inherit;
    }

    .admin-button-danger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.75rem;
        padding: 0 1rem;
        border: 0;
        border-radius: 0.95rem;
        background: linear-gradient(135deg, #ff6d62, #ff4a61);
        color: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .admin-products-table td:first-child {
        white-space: normal;
    }

    .admin-products-table tbody tr[data-sortable='1'] {
        cursor: grab;
        will-change: transform;
        transition:
            transform 0.22s cubic-bezier(0.22, 1, 0.36, 1),
            box-shadow 0.22s cubic-bezier(0.22, 1, 0.36, 1),
            background-color 0.18s ease,
            opacity 0.18s ease;
    }

    .admin-products-table tbody tr[data-sortable='1']:active {
        cursor: grabbing;
    }

    .admin-products-table tbody tr[data-sortable='1']:hover {
        background: rgba(204, 106, 71, 0.04);
    }

    .admin-products-table tbody tr[data-sortable='1'] td {
        transition: background-color 0.18s ease;
    }

    .admin-products-table tbody tr.is-chosen td {
        background: rgba(255, 255, 255, 0.98);
    }

    .admin-products-table tbody tr.is-ghost td {
        background: rgba(204, 106, 71, 0.05);
    }

    .admin-product-name-cell {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 18rem;
    }

    .admin-row-handle {
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 0.85rem;
        background: rgba(27, 36, 48, 0.05);
        font-size: 0.86rem;
        flex: 0 0 auto;
    }

    .admin-row-handle.is-disabled {
        opacity: 0.46;
    }

    .admin-status-toggle-form {
        display: inline-flex;
    }

    .admin-status-toggle {
        border: 0;
        cursor: pointer;
    }

    @media (max-width: 62rem) {
        .admin-product-categories-head,
        .admin-product-table-head,
        .admin-category-manage-bar {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-category-create-form,
        .admin-product-table-head .admin-button,
        .admin-category-manage-actions,
        .admin-category-rename-form,
        .admin-category-rename-form input,
        .admin-category-manage-actions .admin-button,
        .admin-category-manage-actions .admin-button-danger {
            width: 100%;
        }
    }

    @media (max-width: 44rem) {
        .admin-category-tab {
            min-height: 2.8rem;
            padding: 0 0.95rem;
        }

        .admin-product-name-cell {
            min-width: 14rem;
        }
    }
</style>
@endpush

@section('content')
    <section class="admin-card admin-product-categories-card">
        <div class="admin-product-categories-head">
            <div>
                <h2>{{ $selectedCategorySlug === 'all' ? '全部分类' : $selectedCategoryName }}</h2>
            </div>

            <form class="admin-category-create-form" action="{{ route('admin.products.categories.store') }}" method="POST">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="输入新分类名称" maxlength="30" required>
                <button type="submit" class="admin-button">新增分类</button>
            </form>
        </div>

        @if ($errors->has('name'))
            <div class="admin-product-create-errors">
                <p>{{ $errors->first('name') }}</p>
            </div>
        @endif

        <div id="categoryTabs" class="admin-category-tabs admin-category-tabs-drag" data-save-url="{{ route('admin.products.categories.order') }}">
            <a href="{{ route('admin.products') }}" class="admin-category-tab {{ $selectedCategorySlug === 'all' ? 'is-active' : '' }}">
                <span>全部分类</span>
                <strong>{{ $totalProducts }}</strong>
            </a>
            @foreach ($categories as $category)
                <a
                    href="{{ route('admin.products', ['category' => $category['id']]) }}"
                    class="admin-category-tab is-draggable {{ $selectedCategorySlug === $category['id'] ? 'is-active' : '' }}"
                    data-category-id="{{ $category['id'] }}"
                >
                    <span>{{ $category['name'] }}</span>
                    <strong>{{ $category['product_count'] }}</strong>
                    <span class="admin-category-tab-handle" aria-hidden="true">⋮⋮</span>
                </a>
            @endforeach
        </div>

        @if ($selectedCategorySlug !== 'all')
            <div class="admin-category-manage-bar">
                <div class="admin-category-manage-label">
                    <strong>{{ $selectedCategoryName }}</strong>
                    <span class="admin-sort-hint-inline">可拖拽调整展示顺序</span>
                </div>
                <div class="admin-category-manage-actions">
                    <form class="admin-category-rename-form" action="{{ route('admin.products.categories.update', $selectedCategorySlug) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="text" name="rename_name" value="{{ old('rename_name', $selectedCategoryName) }}" maxlength="30" required>
                        <button type="submit" class="admin-button">重命名</button>
                    </form>
                    <form
                        action="{{ route('admin.products.categories.destroy', $selectedCategorySlug) }}"
                        method="POST"
                        data-confirm-title="删除分类"
                        data-confirm-message="确认删除这个分类吗？分类下如仍有关联商品将无法删除。"
                        data-confirm-confirm-text="确认删除"
                        data-confirm-variant="danger"
                    >
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-button-danger">删除分类</button>
                    </form>
                </div>
            </div>
        @endif
    </section>

    <section class="admin-card admin-product-table-card">
        <div class="admin-product-table-head">
            <div>
                <h2>{{ $selectedCategoryName }} 商品</h2>
            </div>

            <a href="{{ $selectedCategorySlug === 'all' ? route('admin.products.create') : route('admin.products.create', ['category' => $selectedCategorySlug]) }}" class="admin-button">新增商品</a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table admin-products-table">
                <thead>
                    <tr>
                        <th>商品 @if ($selectedCategorySlug !== 'all')<span class="admin-sort-hint-th">可拖拽调整展示顺序</span>@endif</th>
                        <th>分类</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>已售</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody
                    id="productSortTable"
                    data-save-url="{{ route('admin.products.items.order') }}"
                    data-category-id="{{ $selectedCategorySlug }}"
                    data-sort-enabled="{{ $selectedCategorySlug === 'all' ? '0' : '1' }}"
                >
                    @forelse ($products as $product)
                        <tr data-product-sku="{{ $product['sku'] }}" data-sortable="{{ $selectedCategorySlug === 'all' ? '0' : '1' }}">
                            <td>
                                <div class="admin-product-name-cell">
                                    <span class="admin-row-handle {{ $selectedCategorySlug === 'all' ? 'is-disabled' : '' }}" aria-hidden="true">⋮⋮</span>
                                    <strong class="admin-product-name">{{ $product['name'] }}</strong>
                                </div>
                            </td>
                            <td>{{ $product['category_name'] }}</td>
                            <td>￥{{ $product['price'] }}</td>
                            <td>{{ $product['stock'] }}</td>
                            <td>{{ $product['sold_count'] }}</td>
                            <td>
                                <form class="admin-status-toggle-form" action="{{ route('admin.products.status', $product['id']) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="category" value="{{ $selectedCategorySlug === 'all' ? $product['category_slug'] : $selectedCategorySlug }}">
                                    <button type="submit" class="status-tag admin-status-toggle {{ $product['status'] === '上架中' ? 'status-tag-live' : 'status-tag-muted' }}">{{ $product['status'] }}</button>
                                </form>
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                    <a href="{{ route('admin.products.edit', ['product' => $product['id'], 'category' => $selectedCategorySlug === 'all' ? $product['category_slug'] : $selectedCategorySlug]) }}" class="admin-action-chip">编辑</a>
                                    <a href="{{ route('admin.cards', ['category' => $product['category_slug'], 'product' => $product['id']]) }}" class="admin-action-chip">卡密</a>
                                    <form
                                        action="{{ route('admin.products.destroy', $product['id']) }}"
                                        method="POST"
                                        data-confirm-title="删除商品"
                                        data-confirm-message="删除该商品连带卡密也会一并删除，确认要继续吗？"
                                        data-confirm-message-html="删除该商品连带<strong>卡密</strong>也会一并删除，确认要继续吗？"
                                        data-confirm-confirm-text="确认删除"
                                        data-confirm-delay-seconds="5"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="category" value="{{ $selectedCategorySlug === 'all' ? $product['category_slug'] : $selectedCategorySlug }}">
                                        <button type="submit" class="admin-action-chip admin-action-chip-danger">删除商品</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state empty-state-tight">当前分类暂无商品。</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const saveState = document.getElementById('catalogSaveState');
        let sortablePromise = null;

        const setState = (message, state = 'idle') => {
            if (!saveState) {
                return;
            }

            saveState.dataset.state = state;
            saveState.textContent = message;

            if (!['success', 'error'].includes(state)) {
                return;
            }

            const content = (message || '').toString().trim();
            if (!content) {
                return;
            }

            window.dispatchEvent(new CustomEvent('admin:toast', {
                detail: {
                    message: content,
                    state,
                },
            }));
        };

        const postJson = async (url, payload, fallbackMessage) => {
            if (!url) {
                return;
            }

            setState('保存中...', 'saving');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || '保存失败。');
                }

                setState(data.message || fallbackMessage, 'success');
            } catch (error) {
                setState(error.message || '保存失败。', 'error');
            }
        };

        const loadSortable = () => {
            if (window.Sortable) {
                return Promise.resolve(window.Sortable);
            }

            if (sortablePromise) {
                return sortablePromise;
            }

            sortablePromise = new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-sortablejs="1"]');

                if (existing) {
                    existing.addEventListener('load', () => resolve(window.Sortable), { once: true });
                    existing.addEventListener('error', () => reject(new Error('拖拽库加载失败。')), { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = `{{ asset('admin-sortable.min.js') }}`;
                script.dataset.sortablejs = '1';
                script.onload = () => resolve(window.Sortable);
                script.onerror = () => reject(new Error('拖拽库加载失败。'));
                document.head.appendChild(script);
            });

            return sortablePromise;
        };

        const boot = async () => {
            const SortableLib = await loadSortable();
            if (!SortableLib) {
                return;
            }

            const baseOptions = {
                animation: 220,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                ghostClass: 'is-ghost',
                chosenClass: 'is-chosen',
                dragClass: 'is-dragging',
                delayOnTouchOnly: true,
                delay: 110,
                touchStartThreshold: 4,
                fallbackTolerance: 4,
            };

            const categoryTabs = document.getElementById('categoryTabs');
            if (categoryTabs && !categoryTabs.dataset.sortableBound) {
                categoryTabs.dataset.sortableBound = '1';
                SortableLib.create(categoryTabs, {
                    ...baseOptions,
                    draggable: '.admin-category-tab[data-category-id]',
                    direction: 'horizontal',
                    onEnd: async (event) => {
                        if (event.oldDraggableIndex === event.newDraggableIndex) {
                            return;
                        }

                        const ids = Array.from(categoryTabs.querySelectorAll('.admin-category-tab[data-category-id]'))
                            .map((item) => item.dataset.categoryId)
                            .filter(Boolean);

                        await postJson(categoryTabs.dataset.saveUrl, { ids }, '分类顺序已保存。');
                    },
                });
            }

            const productSortTable = document.getElementById('productSortTable');
            if (productSortTable && productSortTable.dataset.sortEnabled === '1' && !productSortTable.dataset.sortableBound) {
                productSortTable.dataset.sortableBound = '1';
                SortableLib.create(productSortTable, {
                    ...baseOptions,
                    draggable: 'tr[data-product-sku]',
                    direction: 'vertical',
                    filter: 'a, button, input, textarea, select, label, form',
                    preventOnFilter: false,
                    onEnd: async (event) => {
                        if (event.oldDraggableIndex === event.newDraggableIndex) {
                            return;
                        }

                        const ids = Array.from(productSortTable.querySelectorAll('tr[data-product-sku]'))
                            .map((row) => row.dataset.productSku)
                            .filter(Boolean);

                        await postJson(productSortTable.dataset.saveUrl, {
                            category_id: productSortTable.dataset.categoryId,
                            ids,
                        }, '商品顺序已保存。');
                    },
                });
            }
        };

        boot().catch((error) => {
            setState(error.message || '拖拽初始化失败。', 'error');
        });
    })();
</script>
@endpush
