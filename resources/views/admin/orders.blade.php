@extends('admin.layout')

@push('head')
<style>
.admin-orders-shell{display:grid;gap:.8rem}.admin-orders-section{display:grid;gap:.8rem;padding:1.15rem 1.25rem}.admin-orders-shell>.admin-orders-section:nth-child(-n+2){padding:.88rem 1rem;gap:.56rem}.admin-orders-head{display:flex;align-items:center;justify-content:space-between;gap:.6rem;flex-wrap:wrap}.admin-orders-head h2{margin:0;font-size:.98rem}.admin-orders-head span{color:var(--muted);font-size:.84rem;font-weight:700}.admin-orders-table th.is-time,.admin-orders-table td.is-time{white-space:nowrap;min-width:10.5rem}.admin-orders-table th.is-pickup-code,.admin-orders-table td.is-pickup-code{white-space:nowrap;min-width:7.25rem}.admin-orders-pickup-code{font-family:Consolas,Monaco,monospace;font-size:.9rem;letter-spacing:.06em}.admin-orders-pickup-code.is-empty{color:var(--muted);letter-spacing:normal}.admin-orders-tabs{display:flex;gap:.45rem;flex-wrap:wrap}.admin-orders-tab,.admin-orders-product-card{display:grid;gap:.14rem;padding:.56rem .78rem;border:1px solid rgba(27,36,48,.08);border-radius:.9rem;background:rgba(255,255,255,.78);color:var(--ink);text-decoration:none;transition:border-color .18s ease,background-color .18s ease}.admin-orders-tab strong,.admin-orders-product-card strong{font-size:.94rem;line-height:1.22}.admin-orders-tab span,.admin-orders-product-card span{color:var(--muted);font-size:.82rem}.admin-orders-tab.is-active,.admin-orders-product-card.is-active{border-color:rgba(204,106,71,.24);background:rgba(204,106,71,.12)}.admin-orders-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(15rem,1fr));gap:.5rem}.admin-orders-empty{border:1px dashed var(--line);border-radius:1rem;padding:1.5rem 1rem;text-align:center;color:var(--muted);background:rgba(255,255,255,.58)}.admin-orders-table-wrap{overflow-x:auto}.admin-orders-table{width:100%;border-collapse:collapse;min-width:70rem}.admin-orders-table th,.admin-orders-table td{padding:1rem .75rem;border-bottom:1px solid rgba(27,36,48,.08);text-align:left;vertical-align:middle}.admin-orders-table th{font-size:.88rem;color:var(--muted);font-weight:700}.admin-order-status{display:inline-flex;align-items:center;justify-content:center;min-height:2rem;padding:0 .82rem;border-radius:999rem;font-size:.82rem;font-weight:800}.admin-order-status.is-pending{background:rgba(245,186,73,.18);color:#8b5e00}.admin-order-status.is-paid{background:rgba(49,120,245,.14);color:#2452a7}.admin-order-status.is-delivered{background:rgba(18,113,71,.12);color:#127147}.admin-order-status.is-muted{background:rgba(27,36,48,.08);color:var(--muted)}.admin-order-actions{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}.admin-button-neutral{display:inline-flex;align-items:center;justify-content:center;min-height:2.75rem;padding:0 1rem;border:1px solid rgba(27,36,48,.08);border-radius:.95rem;background:rgba(244,247,251,.96);color:var(--ink);text-decoration:none;font-size:.92rem;font-weight:800}.admin-orders-table-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding-top:1rem}.admin-orders-footer-left,.admin-orders-footer-right,.admin-orders-per-page{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}.admin-orders-select{min-height:2.8rem;padding:0 .9rem;border:1px solid rgba(27,36,48,.1);border-radius:.95rem;background:rgba(255,255,255,.96);color:var(--ink);font:inherit}.admin-orders-page-text{color:var(--muted);font-size:.86rem;font-weight:700}@media (max-width:62rem){.admin-orders-table{min-width:62rem}}@media (max-width:48rem){.admin-orders-products{grid-template-columns:1fr}.admin-orders-tab,.admin-orders-product-card{width:100%}}
</style>
@endpush

@section('content')
@php($selectedCategorySlug = $selectedCategory?->slug)
@php($selectedProductId = $selectedProduct?->id)

<div class="admin-orders-shell">
    <section class="admin-card admin-orders-section">
        <div class="admin-orders-head">
            <h2>商品分类</h2>
            <span>{{ $categories->count() }} 个</span>
        </div>

        @if ($categories->count())
            <div class="admin-orders-tabs">
                @foreach ($categories as $category)
                    <a href="{{ route('admin.orders', ['category' => $category->slug, 'per_page' => $perPage]) }}" class="admin-orders-tab {{ $selectedCategory?->id === $category->id ? 'is-active' : '' }}">
                        <strong>{{ $category->name }}</strong>
                    </a>
                @endforeach
            </div>
        @else
            <div class="admin-orders-empty">暂无分类</div>
        @endif
    </section>

    <section class="admin-card admin-orders-section">
        <div class="admin-orders-head">
            <h2>商品</h2>
            <span>{{ $products->count() }} 个</span>
        </div>

        @if ($selectedCategory && $products->count())
            <div class="admin-orders-products">
                @foreach ($products as $product)
                    <a href="{{ route('admin.orders', ['category' => $selectedCategorySlug, 'product' => $product->id, 'per_page' => $perPage]) }}" class="admin-orders-product-card {{ $selectedProduct?->id === $product->id ? 'is-active' : '' }}">
                        <strong>{{ $product->name }}</strong>
                        <span>{{ $product->orders_count ?? 0 }} 条订单</span>
                    </a>
                @endforeach
            </div>
        @elseif ($selectedCategory)
            <div class="admin-orders-empty">暂无商品</div>
        @else
            <div class="admin-orders-empty">请先选择分类</div>
        @endif
    </section>

    <section class="admin-card admin-orders-section">
        <div class="admin-orders-head">
            <h2>订单信息</h2>
            <span>{{ $orders->total() }} 条订单</span>
        </div>

        @if ($selectedProduct && $orders->count())
            <div class="admin-orders-table-wrap">
                <table class="admin-orders-table">
                    <thead>
                        <tr>
                            <th class="is-time">时间</th>
                            <th>订单号</th>
                            <th>联系方式</th>
                            <th class="is-pickup-code">取货码</th>
                            <th>数量</th>
                            <th>金额</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php($statusClass = match ($order->status) {'待支付' => 'is-pending', '已支付' => 'is-paid', '已发货' => 'is-delivered', default => 'is-muted'})
                            @php($pickupCode = $order->pickupCodeForAdmin())
                            <tr>
                                <td class="is-time">{{ $order->created_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td>{{ $order->order_no }}</td>
                                <td>{{ $order->contact }}</td>
                                <td class="is-pickup-code"><span class="admin-orders-pickup-code {{ $pickupCode ? '' : 'is-empty' }}">{{ $pickupCode ?: '--' }}</span></td>
                                <td>{{ $order->quantity }}</td>
                                <td>&yen;{{ number_format((float) $order->amount, 2) }}</td>
                                <td><span class="admin-order-status {{ $statusClass }}">{{ $order->status }}</span></td>
                                <td>
                                    <div class="admin-order-actions">
                                        <a class="admin-button-neutral" href="{{ route('admin.orders.show', ['order' => $order->id, 'category' => $selectedCategorySlug, 'product' => $selectedProductId, 'page' => $orders->currentPage(), 'per_page' => $perPage]) }}">详情</a>
                                        @if (in_array($order->status, ['待支付', '已支付'], true))
                                            @if ($order->show_stock_shortage ?? false)
                                                <button type="button" class="admin-button" data-stock-shortage-trigger>确认收款并发货</button>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.orders.fulfill', $order) }}"
                                                    data-confirm-title="确认收款并发货？"
                                                    data-confirm-message="执行后将立即标记订单已收款，并按当前发卡顺序自动发卡。确认继续吗？"
                                                    data-confirm-confirm-text="确认并发货"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
                                                    <input type="hidden" name="product" value="{{ $selectedProductId }}">
                                                    <input type="hidden" name="page" value="{{ $orders->currentPage() }}">
                                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                                                    <button type="submit" class="admin-button">确认收款并发货</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-orders-table-footer">
                <div class="admin-orders-footer-left">
                    @if ($orders->onFirstPage())
                        <span class="admin-button-neutral">上一页</span>
                    @else
                        <a class="admin-button-neutral" href="{{ $orders->previousPageUrl() }}">上一页</a>
                    @endif

                    @if ($orders->hasMorePages())
                        <a class="admin-button-neutral" href="{{ $orders->nextPageUrl() }}">下一页</a>
                    @else
                        <span class="admin-button-neutral">下一页</span>
                    @endif
                </div>

                <div class="admin-orders-footer-right">
                    <span class="admin-orders-page-text">当前第 {{ $orders->currentPage() }} / {{ $orders->lastPage() }} 页</span>
                    <form method="GET" class="admin-orders-per-page">
                        <input type="hidden" name="category" value="{{ $selectedCategorySlug }}">
                        <input type="hidden" name="product" value="{{ $selectedProductId }}">
                        <select name="per_page" class="admin-orders-select" onchange="this.form.submit()">
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}" {{ $perPage === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        @elseif ($selectedProduct)
            <div class="admin-orders-empty">暂无订单</div>
        @else
            <div class="admin-orders-empty">请先选择商品</div>
        @endif
    </section>
</div>

@include('admin.partials.stock-shortage-toast')
@endsection
