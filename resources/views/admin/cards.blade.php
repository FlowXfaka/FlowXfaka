@extends('admin.layout')

@push('head')
<style>
    .admin-cards-page {
        display: grid;
        gap: 1.25rem;
    }

    .admin-cards-topbar {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .admin-cards-back {
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

    .admin-cards-title {
        margin: 0;
        font-size: clamp(1.7rem, 2.5vw, 2.25rem);
    }

    .admin-cards-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .admin-cards-panel {
        display: grid;
        gap: 1rem;
    }

    .admin-cards-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .admin-cards-panel-head-actions {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-cards-panel-head h2,
    .admin-cards-table-head h2,
    .admin-card-row-id,
    .admin-card-updated,
    .admin-card-table input[type='text'] {
        margin: 0;
    }

    .admin-cards-panel-head h2,
    .admin-cards-table-head h2 {
        font-size: clamp(1.2rem, 1.6vw, 1.6rem);
    }

    .admin-cards-stock {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0 0.8rem;
        border-radius: 999rem;
        background: rgba(18, 113, 71, 0.1);
        color: #127147;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .admin-card-import-form {
        display: grid;
        gap: 0.85rem;
    }

    .admin-card-import-form textarea {
        width: 100%;
        min-height: 13rem;
        padding: 1rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 1rem;
        background: rgba(249, 251, 254, 0.96);
        color: var(--ink);
        font: inherit;
        resize: vertical;
    }

    .admin-card-import-form textarea:focus,
    .admin-card-input:focus {
        outline: none;
        border-color: rgba(204, 106, 71, 0.32);
    }

    .admin-card-submit {
        width: 100%;
        min-height: 3rem;
        border: 0;
        border-radius: 1rem;
        background: linear-gradient(135deg, #ff8846, #ff5b4e);
        color: #fff;
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .admin-cards-import-shortcut {
        white-space: nowrap;
    }

    .admin-cards-file-input {
        display: none;
    }

    .admin-cards-table-wrap {
        overflow-x: auto;
    }

    .admin-cards-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.35rem;
    }

    .admin-cards-bulk-actions {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
    }

    .admin-cards-dispatch-form {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .admin-cards-dispatch-label {
        color: var(--muted);
        font-size: 0.92rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .admin-cards-dispatch-select {
        min-height: 2.5rem;
        padding: 0 0.9rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.96);
        color: var(--ink);
        font: inherit;
        font-size: 0.92rem;
        font-weight: 600;
    }

    .admin-cards-dispatch-select:focus {
        outline: none;
        border-color: rgba(204, 106, 71, 0.32);
    }

    .admin-card-check-toggle,
    .admin-card-row-check {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--muted);
        font-size: 0.92rem;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
    }

    .admin-card-row-check {
        color: var(--ink);
        justify-content: center;
    }

    .admin-card-check-toggle input,
    .admin-card-row-check input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .admin-card-check-box {
        position: relative;
        width: 1.15rem;
        height: 1.15rem;
        border: 1px solid rgba(27, 36, 48, 0.18);
        border-radius: 0.28rem;
        background: rgba(255, 255, 255, 0.96);
        flex: 0 0 auto;
    }

    .admin-card-check-box::after {
        content: '';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 900;
        line-height: 1;
    }

    .admin-card-check-toggle input:checked + .admin-card-check-box,
    .admin-card-row-check input:checked + .admin-card-check-box {
        border-color: rgba(204, 106, 71, 0.9);
        background: linear-gradient(135deg, #cc6a47, #b95734);
    }

    .admin-card-check-toggle input:checked + .admin-card-check-box::after,
    .admin-card-row-check input:checked + .admin-card-check-box::after {
        content: '\2713';
    }

    .admin-cards-bulk-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.5rem;
        padding: 0 0.95rem;
        border: 0;
        border-radius: 0.95rem;
        background: rgba(244, 176, 183, 0.7);
        color: rgba(255, 255, 255, 0.92);
        font: inherit;
        font-weight: 800;
        cursor: not-allowed;
        transition: background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    }

    .admin-cards-bulk-button.is-active,
    .admin-cards-bulk-button:not(:disabled) {
        background: linear-gradient(135deg, #ff8846, #ff5b4e);
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(255, 107, 78, 0.18);
    }

    .admin-card-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 46rem;
    }

    .admin-card-table th,
    .admin-card-table td {
        padding: 0.9rem 0.75rem;
        border-bottom: 1px solid rgba(27, 36, 48, 0.08);
        text-align: left;
        vertical-align: middle;
    }

    .admin-card-table th {
        color: var(--muted);
        font-size: 0.88rem;
        font-weight: 700;
    }

    .admin-card-table tbody tr:hover {
        background: rgba(204, 106, 71, 0.03);
    }

    .admin-card-row-id {
        color: var(--ink);
        font-weight: 700;
    }

    .admin-card-row-form {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .admin-card-input {
        width: 100%;
        min-height: 2.45rem;
        padding: 0 0.8rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 0.9rem;
        background: rgba(250, 251, 254, 0.96);
        color: var(--ink);
        font: inherit;
    }

    .admin-card-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.35rem;
        padding: 0 0.85rem;
        border: 0;
        border-radius: 0.85rem;
        background: rgba(240, 243, 248, 0.96);
        color: var(--ink);
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
    }

    .admin-card-save:hover {
        color: var(--accent-deep);
    }

    .admin-card-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0 0.85rem;
        border-radius: 999rem;
        background: rgba(245, 186, 73, 0.18);
        color: #8b5e00;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .admin-card-status.is-used {
        background: rgba(27, 36, 48, 0.08);
        color: var(--muted);
    }

    .admin-card-updated {
        color: var(--ink);
        white-space: nowrap;
    }

    .admin-cards-empty {
        padding: 3rem 1.25rem;
        border: 1px dashed rgba(27, 36, 48, 0.12);
        border-radius: 1.25rem;
        color: var(--muted);
        text-align: center;
        background: rgba(255, 255, 255, 0.66);
    }

    .admin-cards-table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-top: 1rem;
    }

    .admin-cards-pagination {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .admin-button-neutral {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.5rem;
        padding: 0 1rem;
        border: 1px solid rgba(27, 36, 48, 0.08);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.96);
        color: var(--ink);
        text-decoration: none;
        font: inherit;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
        transition: border-color 0.18s ease, color 0.18s ease, background-color 0.18s ease;
    }

    a.admin-button-neutral:hover,
    button.admin-button-neutral:hover {
        border-color: rgba(204, 106, 71, 0.24);
        color: var(--accent-deep);
        background: rgba(255, 248, 244, 0.96);
    }

    span.admin-button-neutral {
        color: var(--muted);
        background: rgba(245, 247, 250, 0.96);
    }

    .admin-cards-export-button.is-active {
        border-color: rgba(204, 106, 71, 0.24);
        color: var(--accent-deep);
        background: rgba(255, 248, 244, 0.96);
    }

    button.admin-button-neutral:disabled {
        color: var(--muted);
        background: rgba(245, 247, 250, 0.96);
        cursor: not-allowed;
    }

    .admin-cards-footer-left,
    .admin-cards-footer-right,
    .admin-cards-per-page {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }

    .admin-cards-select {
        min-height: 2.8rem;
        padding: 0 0.9rem;
        border: 1px solid rgba(27, 36, 48, 0.1);
        border-radius: 0.95rem;
        background: rgba(255, 255, 255, 0.96);
        color: var(--ink);
        font: inherit;
    }

    .admin-cards-page-text {
        color: var(--muted);
        font-size: 0.86rem;
        font-weight: 700;
    }

    @media (max-width: 72rem) {
        .admin-cards-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 50rem) {
        .admin-cards-topbar,
        .admin-cards-table-head,
        .admin-cards-panel-head,
        .admin-cards-table-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .admin-cards-bulk-actions,
        .admin-cards-footer-left,
        .admin-cards-footer-right {
            width: 100%;
            justify-content: space-between;
        }

        .admin-cards-panel-head-actions {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
@endpush

@section('content')
    @php($backCategory = $selectedCategory?->slug)
    @php($currentPage = $cards?->currentPage() ?? 1)
    @php($deliveredPage = $deliveredCards?->currentPage() ?? 1)

    <div class="admin-cards-page">
        <div class="admin-cards-topbar">
            <a href="{{ route('admin.products', ['category' => $backCategory]) }}" class="admin-cards-back">返回</a>
            <h1 class="admin-cards-title">{{ $selectedProduct?->name ?? '卡密管理' }}</h1>
        </div>

        @if ($errors->any())
            <div class="admin-product-create-errors">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($selectedProduct && $cards)
            <div class="admin-cards-grid">
                <section class="admin-card admin-cards-panel admin-cards-panel--import">
                    <div class="admin-cards-panel-head">
                        <h2>一行一条卡密</h2>
                        <div class="admin-cards-panel-head-actions">
                            <button type="button" class="admin-button-neutral admin-cards-import-shortcut" id="cardImportFileButton">导入卡密</button>
                            <span class="admin-cards-stock">当前库存 {{ $availableCount }}</span>
                        </div>
                    </div>

                    <form id="cardImportForm" class="admin-card-import-form" action="{{ route('admin.cards.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                        <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                        <input type="hidden" name="page" value="{{ $currentPage }}">
                        <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <input id="cardImportFileInput" class="admin-cards-file-input" type="file" accept=".txt,text/plain">
                        <textarea name="card_values" placeholder="卡密001&#10;卡密002&#10;卡密003" required>{{ old('card_values') }}</textarea>
                        <button type="submit" class="admin-card-submit">导入库存</button>
                    </form>
                </section>

                <section class="admin-card admin-cards-panel">
                    <div class="admin-cards-table-head">
                        <h2>可售卡密 {{ $cards->total() }} 条</h2>

                        <div class="admin-cards-bulk-actions">
                            <form class="admin-cards-dispatch-form" action="{{ route('admin.cards.dispatch-mode') }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                                <input type="hidden" name="page" value="{{ $currentPage }}">
                                <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                                <label class="admin-cards-dispatch-label" for="cardDispatchMode">发卡顺序</label>
                                <select id="cardDispatchMode" name="dispatch_mode" class="admin-cards-dispatch-select" onchange="this.form.submit()">
                                    @foreach ($cardDispatchModeOptions as $dispatchModeValue => $dispatchModeLabel)
                                        <option value="{{ $dispatchModeValue }}" {{ $currentCardDispatchMode === $dispatchModeValue ? 'selected' : '' }}>{{ $dispatchModeLabel }}</option>
                                    @endforeach
                                </select>
                            </form>

                            <label class="admin-card-check-toggle" for="cardToggleAll">
                                <input type="checkbox" id="cardToggleAll">
                                <span class="admin-card-check-box" aria-hidden="true"></span>
                                <span>全选/反选</span>
                            </label>

                            <form id="cardExportForm" action="{{ route('admin.cards.export') }}" method="GET" data-no-spa>
                                <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                                <button type="submit" class="admin-button-neutral admin-cards-export-button" id="cardExportButton" {{ $cards->total() === 0 ? 'disabled' : '' }}>导出卡密</button>
                            </form>

                            <form
                                id="bulkDeleteForm"
                                action="{{ route('admin.cards.bulk-destroy') }}"
                                method="POST"
                                data-confirm-title="删除卡密"
                                data-confirm-message="确认删除选中的卡密吗？删除后无法恢复。"
                                data-confirm-confirm-text="确认删除"
                                data-confirm-variant="danger"
                            >
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                                <input type="hidden" name="page" value="{{ $currentPage }}">
                                <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                                <button type="submit" class="admin-cards-bulk-button" id="bulkDeleteButton" disabled>批量删除</button>
                            </form>
                        </div>
                    </div>

                    @if ($cards->count())
                        <div class="admin-cards-table-wrap">
                            <table class="admin-card-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>序号</th>
                                        <th>卡密</th>
                                        <th>状态</th>
                                        <th>更新时间</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cards as $card)
                                        <tr>
                                            <td>
                                                <label class="admin-card-row-check" for="card-row-{{ $card->id }}">
                                                    <input type="checkbox" id="card-row-{{ $card->id }}" name="ids[]" value="{{ $card->id }}" form="bulkDeleteForm" class="card-row-checkbox">
                                                    <span class="admin-card-check-box" aria-hidden="true"></span>
                                                </label>
                                            </td>
                                            <td><p class="admin-card-row-id">{{ ($cards->firstItem() ?? 0) + $loop->index }}</p></td>
                                            <td>
                                                <form class="admin-card-row-form" action="{{ route('admin.cards.update', $card) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                                    <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                                    <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                                                    <input type="hidden" name="note" value="{{ $card->note }}">
                                                    <input class="admin-card-input" type="text" name="card_values" value="{{ $card->card_value }}" required>
                                                    <button type="submit" class="admin-card-save">保存</button>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="admin-card-status">可售</span>
                                            </td>
                                            <td><p class="admin-card-updated">{{ $card->updated_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '' }}</p></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="admin-cards-table-footer">
                            <div class="admin-cards-footer-left">
                                @if ($cards->onFirstPage())
                                    <span class="admin-button-neutral">上一页</span>
                                @else
                                    <a class="admin-button-neutral" href="{{ $cards->previousPageUrl() }}">上一页</a>
                                @endif

                                @if ($cards->hasMorePages())
                                    <a class="admin-button-neutral" href="{{ $cards->nextPageUrl() }}">下一页</a>
                                @else
                                    <span class="admin-button-neutral">下一页</span>
                                @endif
                            </div>

                            <div class="admin-cards-footer-right">
                                <span class="admin-cards-page-text">当前第 {{ $cards->currentPage() }} / {{ $cards->lastPage() }} 页</span>
                                <form method="GET" class="admin-cards-per-page">
                                    <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                    <input type="hidden" name="product" value="{{ $selectedProduct->id }}">
                                    <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                    <select name="per_page" class="admin-cards-select" onchange="this.form.submit()">
                                        @foreach ($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="admin-cards-empty">当前商品暂无可售卡密</div>
                    @endif
                </section>

                <section class="admin-card admin-cards-panel">
                    <div class="admin-cards-table-head">
                        <h2>已发货卡密 {{ $deliveredCards?->total() ?? 0 }} 条</h2>
                    </div>

                    @if ($deliveredCards && $deliveredCards->count())
                        <div class="admin-cards-table-wrap">
                            <table class="admin-card-table">
                                <thead>
                                    <tr>
                                        <th>&#24207;&#21495;</th>
                                        <th>&#21345;&#23494;</th>
                                        <th>&#35746;&#21333;&#21495;</th>
                                        <th>&#32852;&#31995;&#26041;&#24335;</th>
                                        <th>&#29366;&#24577;</th>
                                        <th>&#26356;&#26032;&#26102;&#38388;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($deliveredCards as $card)
                                        <tr>
                                            <td><p class="admin-card-row-id">{{ ($deliveredCards->firstItem() ?? 0) + $loop->index }}</p></td>
                                            <td>
                                                <form class="admin-card-row-form" action="{{ route('admin.cards.update', $card) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                                    <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
                                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                                    <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                                                    <input type="hidden" name="note" value="{{ $card->note }}">
                                                    <input class="admin-card-input" type="text" name="card_values" value="{{ $card->card_value }}" required>
                                                    <button type="submit" class="admin-card-save">&#26356;&#26032;</button>
                                                </form>
                                            </td>
                                            <td><p class="admin-card-updated">{{ $card->delivery_order_no ?: '--' }}</p></td>
                                            <td><p class="admin-card-updated">{{ $card->delivery_contact ?: '--' }}</p></td>
                                            <td>
                                                <span class="admin-card-status is-used">{{ $card->status }}</span>
                                            </td>
                                            <td><p class="admin-card-updated">{{ $card->updated_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '' }}</p></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="admin-cards-table-footer">
                            <div class="admin-cards-footer-left">
                                @if ($deliveredCards->onFirstPage())
                                    <span class="admin-button-neutral">上一页</span>
                                @else
                                    <a class="admin-button-neutral" href="{{ $deliveredCards->previousPageUrl() }}">上一页</a>
                                @endif

                                @if ($deliveredCards->hasMorePages())
                                    <a class="admin-button-neutral" href="{{ $deliveredCards->nextPageUrl() }}">下一页</a>
                                @else
                                    <span class="admin-button-neutral">下一页</span>
                                @endif
                            </div>

                            <div class="admin-cards-footer-right">
                                <span class="admin-cards-page-text">当前第 {{ $deliveredCards->currentPage() }} / {{ $deliveredCards->lastPage() }} 页</span>
                                <form method="GET" class="admin-cards-per-page">
                                    <input type="hidden" name="category" value="{{ $selectedCategory->slug }}">
                                    <input type="hidden" name="product" value="{{ $selectedProduct->id }}">
                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                    <input type="hidden" name="delivered_page" value="{{ $deliveredPage }}">
                                    <select name="per_page" class="admin-cards-select" onchange="this.form.submit()">
                                        @foreach ($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="admin-cards-empty">当前没有已发货卡密</div>
                    @endif
                </section>
            </div>
        @else
            <div class="admin-cards-empty">当前没有可管理的商品卡密</div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const importForm = document.getElementById('cardImportForm');
        const importTextarea = importForm?.querySelector('textarea[name="card_values"]');
        const importFileButton = document.getElementById('cardImportFileButton');
        const importFileInput = document.getElementById('cardImportFileInput');
        const toggle = document.getElementById('cardToggleAll');
        const checkboxes = [...document.querySelectorAll('.card-row-checkbox')];
        const bulkDeleteButton = document.getElementById('bulkDeleteButton');
        const exportForm = document.getElementById('cardExportForm');
        const exportButton = document.getElementById('cardExportButton');

        const showToast = (message, state = 'success') => {
            window.dispatchEvent(new CustomEvent('admin:toast', {
                detail: {
                    message,
                    state,
                },
            }));
        };

        const decodeCardImportFile = async (file) => {
            const buffer = await file.arrayBuffer();

            try {
                return new TextDecoder('utf-8', { fatal: true }).decode(buffer);
            } catch {
                return new TextDecoder('gb18030').decode(buffer);
            }
        };

        if (importForm && importTextarea && importFileButton && importFileInput) {
            importFileButton.addEventListener('click', () => {
                importFileInput.click();
            });

            importFileInput.addEventListener('change', async () => {
                const file = importFileInput.files?.[0];

                if (!file) {
                    return;
                }

                importFileButton.disabled = true;
                importFileButton.textContent = '读取中...';

                try {
                    const content = await decodeCardImportFile(file);
                    const normalized = content.replace(/\r\n?/g, '\n').trim();

                    if (normalized === '') {
                        showToast('这个 txt 文件里没有可导入的卡密。', 'warning');
                        return;
                    }

                    importTextarea.value = normalized;
                    showToast(`已载入 ${file.name}，正在导入。`, 'success');
                    importForm.requestSubmit();
                } catch {
                    showToast('读取 txt 文件失败，请换一个文件再试。', 'error');
                } finally {
                    importFileInput.value = '';
                    importFileButton.disabled = false;
                    importFileButton.textContent = '导入卡密';
                }
            });
        }

        if (!toggle || !bulkDeleteButton || checkboxes.length === 0) {
            return;
        }

        const syncExportSelection = (selectedCheckboxes) => {
            if (!exportForm) {
                return;
            }

            exportForm.querySelectorAll('input[name="ids[]"]').forEach((input) => {
                input.remove();
            });

            selectedCheckboxes.forEach((item) => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'ids[]';
                hiddenInput.value = item.value;
                exportForm.appendChild(hiddenInput);
            });

            if (exportButton) {
                const hasSelection = selectedCheckboxes.length > 0;
                exportButton.classList.toggle('is-active', hasSelection);
                exportButton.textContent = hasSelection ? `导出选中（${selectedCheckboxes.length}）` : '导出卡密';
                exportButton.title = hasSelection ? '导出当前勾选的可售卡密' : '导出当前商品全部可售卡密';
            }
        };

        const syncSelectionState = () => {
            const selectedCheckboxes = checkboxes.filter((item) => item.checked);
            const selectedCount = selectedCheckboxes.length;
            toggle.checked = selectedCount > 0 && selectedCount === checkboxes.length;
            bulkDeleteButton.disabled = selectedCount === 0;
            bulkDeleteButton.classList.toggle('is-active', selectedCount > 0);
            syncExportSelection(selectedCheckboxes);
        };

        toggle.addEventListener('change', () => {
            checkboxes.forEach((item) => {
                item.checked = toggle.checked;
            });
            syncSelectionState();
        });

        checkboxes.forEach((item) => {
            item.addEventListener('change', syncSelectionState);
        });

        syncSelectionState();
    })();
</script>
@endpush
