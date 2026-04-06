@extends('admin.layout')

@push('head')
<style>
.admin-order-show-stack{display:grid;gap:1rem}.admin-order-show-topbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.admin-order-back{display:inline-flex;align-items:center;justify-content:center;min-height:2.75rem;padding:0 1rem;border:1px solid rgba(27,36,48,.08);border-radius:.95rem;background:rgba(244,247,251,.96);color:var(--ink);text-decoration:none;font-weight:800}.admin-order-show-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(18rem,.85fr);gap:1rem}.admin-order-info-list,.admin-order-card-list{display:grid;gap:.85rem}.admin-order-info-row,.admin-order-card-item{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.95rem 1rem;border:1px solid var(--line);border-radius:1rem;background:rgba(255,255,255,.7)}.admin-order-info-row span{color:var(--muted);font-weight:700}.admin-order-info-row strong,.admin-order-card-item code{color:var(--ink);font-size:.95rem}.admin-order-info-row code,.admin-order-card-item code{font-family:Consolas,Monaco,monospace;word-break:break-all}.admin-order-status{display:inline-flex;align-items:center;justify-content:center;min-height:2rem;padding:0 .82rem;border-radius:999rem;font-size:.82rem;font-weight:800}.admin-order-status.is-pending{background:rgba(245,186,73,.18);color:#8b5e00}.admin-order-status.is-paid{background:rgba(49,120,245,.14);color:#2452a7}.admin-order-status.is-delivered{background:rgba(18,113,71,.12);color:#127147}.admin-order-status.is-muted{background:rgba(27,36,48,.08);color:var(--muted)}.admin-order-empty{border:1px dashed var(--line);border-radius:1.2rem;padding:2.25rem 1rem;text-align:center;color:var(--muted);background:rgba(255,255,255,.58)}@media (max-width:68rem){.admin-order-show-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php($statusClass = match ($order->status) {'待支付' => 'is-pending', '已支付' => 'is-paid', '已发货' => 'is-delivered', default => 'is-muted'})
@php($pickupCode = $order->pickupCodeForAdmin())

<div class="admin-order-show-stack">
    <div class="admin-order-show-topbar">
        <a href="{{ route('admin.orders', $returnQuery) }}" class="admin-order-back">返回</a>

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
                    @foreach ($returnQuery as $queryKey => $queryValue)
                        <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                    @endforeach
                    <button type="submit" class="admin-button">确认收款并发货</button>
                </form>
            @endif
        @endif
    </div>

    <div class="admin-order-show-grid">
        <section class="admin-card">
            <div class="admin-card-head">
                <h2>订单信息</h2>
                <span>{{ $order->order_no }}</span>
            </div>

            <div class="admin-order-info-list">
                <div class="admin-order-info-row">
                    <span>订单号</span>
                    <strong>{{ $order->order_no }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>商品</span>
                    <strong>{{ $order->product?->name ?? '商品不存在' }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>联系方式</span>
                    <strong>{{ $order->contact }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>取货码</span>
                    <strong>
                        @if ($pickupCode)
                            <code>{{ $pickupCode }}</code>
                        @else
                            --
                        @endif
                    </strong>
                </div>
                <div class="admin-order-info-row">
                    <span>数量</span>
                    <strong>{{ $order->quantity }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>金额</span>
                    <strong>&yen;{{ number_format((float) $order->amount, 2) }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>状态</span>
                    <strong><span class="admin-order-status {{ $statusClass }}">{{ $order->status }}</span></strong>
                </div>
                <div class="admin-order-info-row">
                    <span>创建时间</span>
                    <strong>{{ $order->created_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '-' }}</strong>
                </div>
                <div class="admin-order-info-row">
                    <span>发货时间</span>
                    <strong>{{ $order->delivered_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '未发货' }}</strong>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <h2>发货记录</h2>
                <span>{{ $deliveredCards->count() }} 条</span>
            </div>

            @if ($deliveredCards->count() > 0)
                <div class="admin-order-card-list">
                    @foreach ($deliveredCards as $cardValue)
                        <div class="admin-order-card-item">
                            <span>卡密</span>
                            <code>{{ $cardValue }}</code>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="admin-order-empty">当前订单还未发货。</div>
            @endif
        </section>
    </div>
</div>

@include('admin.partials.stock-shortage-toast')
@endsection
