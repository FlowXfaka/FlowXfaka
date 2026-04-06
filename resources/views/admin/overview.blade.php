@extends('admin.layout')

@push('head')
<style>
.overview-filter-bar{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin:0 0 1.1rem}.overview-filter-chip{display:inline-flex;align-items:center;justify-content:center;min-height:2.7rem;padding:0 1.15rem;border:1px solid var(--line);border-radius:999px;background:#fff;color:#3d4b5c;text-decoration:none;font-size:.92rem;font-weight:800;transition:all .18s ease}.overview-filter-chip:hover{border-color:rgba(62,130,240,.28);color:#2e6fdd}.overview-filter-chip.is-active{background:linear-gradient(135deg,#3d7cf2,#4ebcff);border-color:transparent;color:#fff;box-shadow:0 .9rem 1.6rem rgba(63,129,242,.18)}.overview-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.2rem}.overview-stat-card{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr);align-items:center;gap:1rem;padding:1.25rem 1.35rem;border:1px solid var(--line);border-radius:1.5rem;background:var(--panel-strong);box-shadow:var(--shadow)}.overview-stat-icon{width:3.4rem;height:3.4rem;border-radius:1rem;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}.overview-stat-icon svg{width:1.55rem;height:1.55rem}.overview-stat-copy{display:grid;gap:.32rem;min-width:0}.overview-stat-label{margin:0;color:#435163;font-size:.95rem;font-weight:700}.overview-stat-value{margin:0;color:#1b2430;font-size:2rem;line-height:1;font-weight:900}.overview-stat-card.is-blue .overview-stat-icon{background:rgba(73,139,255,.14);color:#3e82f0}.overview-stat-card.is-green .overview-stat-icon{background:rgba(74,190,116,.14);color:#32a85d}.overview-stat-card.is-slate .overview-stat-icon{background:rgba(125,137,158,.13);color:#818b99}.overview-stat-card.is-amber .overview-stat-icon{background:rgba(241,178,48,.15);color:#d18a00}.overview-stat-card.is-red .overview-stat-icon{background:rgba(223,87,87,.14);color:#c64545}.overview-stat-card.is-blue .overview-stat-value{color:#2e6fdd}.overview-stat-card.is-green .overview-stat-value{color:#35a35c}.overview-stat-card.is-slate .overview-stat-value{color:#758093}.overview-stat-card.is-amber .overview-stat-value{color:#d38a00}.overview-stat-card.is-red .overview-stat-value{color:#c74646}.overview-sales-table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:1.15rem;background:var(--panel-strong)}.overview-sales-table{width:100%;min-width:60rem;border-collapse:collapse}.overview-sales-table th,.overview-sales-table td{padding:.95rem 1rem;border-bottom:1px solid rgba(27,36,48,.08);text-align:left;vertical-align:top}.overview-sales-table th{color:#6b7686;font-size:.86rem;font-weight:800;white-space:nowrap;background:rgba(246,249,253,.85)}.overview-sales-table td{color:#1b2430;font-size:.92rem;line-height:1.5}.overview-sales-table tbody tr:last-child td{border-bottom:0}.overview-sales-table code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace;font-size:.84rem;word-break:break-all}.overview-sales-product{display:grid;gap:.15rem;min-width:12rem}.overview-sales-product strong{font-size:.95rem}.overview-sales-product span{color:var(--muted);font-size:.84rem}.overview-sales-amount{font-weight:800;color:#2e6fdd;white-space:nowrap}.overview-threshold-inline{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}.overview-threshold-inline span{color:var(--muted);font-size:.92rem}.overview-threshold-inline input{width:7rem;min-height:2.75rem;padding:0 .9rem;border:1px solid var(--line);border-radius:.95rem;background:#fff}.overview-warning-list{display:grid;gap:.95rem}.overview-warning-item{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;border:1px solid var(--line);border-radius:1.15rem;background:var(--panel-strong)}.overview-warning-item strong{display:block;font-size:.98rem;line-height:1.45}.overview-empty{padding:1.1rem 1rem;border:1px dashed var(--line);border-radius:1.15rem;text-align:center;color:var(--muted);background:var(--panel-strong)}@media (max-width:64rem){.overview-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media (max-width:44rem){.overview-stat-grid{grid-template-columns:1fr}.overview-warning-item,.overview-threshold-inline{align-items:flex-start;flex-direction:column}.overview-threshold-inline input,.overview-threshold-inline button{width:100%}}
</style>
<style>
.overview-custom-range{display:flex;align-items:flex-end;gap:.75rem;flex-wrap:wrap;margin:0 0 1.25rem}.overview-custom-field{display:grid;gap:.4rem}.overview-custom-field span{color:var(--muted);font-size:.88rem;font-weight:700}.overview-custom-field input{min-height:2.75rem;padding:0 .95rem;border:1px solid var(--line);border-radius:.95rem;background:#fff;min-width:13rem}.overview-custom-range .admin-button{min-height:2.75rem;padding:0 1.1rem}@media (max-width:44rem){.overview-custom-range{align-items:stretch}.overview-custom-field,.overview-custom-field input,.overview-custom-range .admin-button{width:100%}}
</style>
@endpush

@section('content')
    @php
        $statDecor = [
            ['tone' => 'blue', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5z"/><path d="M12 4v16"/><path d="M4 8.5 12 13l8-4.5"/></svg>'],
            ['tone' => 'green', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>'],
            ['tone' => 'slate', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/></svg>'],
            ['tone' => 'blue', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h8"/><path d="M8 12h8"/><path d="M8 18h8"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/></svg>'],
            ['tone' => 'green', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="6" width="16" height="12" rx="2"/><path d="M8 12h8"/><path d="M8 9h.01"/></svg>'],
            ['tone' => 'amber', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4 4 19h16L12 4z"/><path d="M12 10v4"/><path d="M12 17h.01"/></svg>'],
            ['tone' => 'blue', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="6" width="16" height="12" rx="2"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>'],
            ['tone' => 'green', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3.5" y="6.5" width="17" height="11" rx="2"/><path d="M7 12h10"/><path d="M8 9h.01"/></svg>'],
            ['tone' => 'red', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v10"/><path d="M12 17h.01"/><path d="M5 21h14"/><path d="M7 21V9a5 5 0 0 1 10 0v12"/></svg>'],
        ];
    @endphp

    <nav class="overview-filter-bar" aria-label="概览时间筛选">
        @foreach ($overviewPeriods as $periodKey => $periodLabel)
            <a class="overview-filter-chip {{ $activePeriod === $periodKey ? 'is-active' : '' }}" href="{{ route('admin.overview', ['period' => $periodKey]) }}">{{ $periodLabel }}</a>
        @endforeach
    </nav>

    @if ($activePeriod === 'custom')
        <form class="overview-custom-range" action="{{ route('admin.overview') }}" method="GET">
            <input type="hidden" name="period" value="custom">
            <label class="overview-custom-field">
                <span>开始时间</span>
                <input type="datetime-local" name="start_at" value="{{ $customStart }}">
            </label>
            <label class="overview-custom-field">
                <span>结束时间</span>
                <input type="datetime-local" name="end_at" value="{{ $customEnd }}">
            </label>
            <button class="admin-button" type="submit">确定</button>
        </form>
    @endif

    <section class="overview-stat-grid">
        @foreach ($stats as $index => $stat)
            @php($decor = $statDecor[$index] ?? ['tone' => 'blue', 'icon' => ''])
            <article class="overview-stat-card is-{{ $decor['tone'] }}">
                <span class="overview-stat-icon">{!! $decor['icon'] !!}</span>
                <div class="overview-stat-copy">
                    <p class="overview-stat-label">{{ $stat['label'] }}</p>
                    <strong class="overview-stat-value">{{ $stat['value'] }}</strong>
                </div>
            </article>
        @endforeach
    </section>

    <section class="admin-card" style="margin-top:1.25rem;">
        <div class="admin-card-head">
            <h2>库存预警</h2>
            <form class="overview-threshold-inline" action="{{ route('admin.overview.low-stock-threshold') }}" method="POST">
                @csrf
                <input type="hidden" name="period" value="{{ $activePeriod }}">
                @if ($activePeriod === 'custom')
                    <input type="hidden" name="start_at" value="{{ $customStart }}">
                    <input type="hidden" name="end_at" value="{{ $customEnd }}">
                @endif
                <span>库存小于</span>
                <input type="number" name="low_stock_threshold" min="0" max="99999" value="{{ $lowStockThreshold }}">
                <button class="admin-button" type="submit">保存</button>
            </form>
        </div>

        @if ($lowStockProducts->count())
            <div class="overview-warning-list">
                @foreach ($lowStockProducts as $product)
                    <div class="overview-warning-item">
                        <strong>{{ $product->name }}</strong>
                        <span class="status-tag">库存 {{ $product->available_cards_count }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="overview-empty">暂无库存预警</div>
        @endif
    </section>

    <section class="admin-card" style="margin-top:1.25rem;">
        <div class="admin-card-head">
            <h2>最近售卖 10 条</h2>
        </div>

        @if (($recentSales ?? collect())->count())
            <div class="overview-sales-table-wrap">
                <table class="overview-sales-table">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>商品</th>
                            <th>分类</th>
                            <th>数量</th>
                            <th>金额</th>
                            <th>订单号</th>
                            <th>联系方式</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr>
                                <td>{{ $sale['time'] }}</td>
                                <td>
                                    <div class="overview-sales-product">
                                        <strong>{{ $sale['product_name'] }}</strong>
                                    </div>
                                </td>
                                <td>{{ $sale['category_name'] }}</td>
                                <td>{{ $sale['quantity'] }}</td>
                                <td class="overview-sales-amount">¥{{ $sale['amount'] }}</td>
                                <td><code>{{ $sale['order_no'] }}</code></td>
                                <td>{{ $sale['contact'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="overview-empty">当前筛选范围内暂无最近售卖记录</div>
        @endif
    </section>
@endsection
