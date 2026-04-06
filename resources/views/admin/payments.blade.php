@extends('admin.layout')

@push('head')
<style>
.payment-route-cell {
    min-width: 10rem;
}
</style>
@endpush

@section('content')
    @php
        $providerOptions = $providerOptions ?? [];
    @endphp
    <section class="admin-card">
        <div class="admin-card-head admin-card-head--stack">
            <div>
                <h2>&#25903;&#20184;&#36890;&#36947;</h2>
            </div>
            <a class="admin-button" href="{{ route('admin.payments.create') }}" data-no-spa>+ &#26032;&#22686;</a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table admin-payment-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>&#25903;&#20184;&#21517;&#31216;</th>
                        <th>&#25903;&#20184;&#25552;&#20379;&#26041;</th>
                        <th>&#25903;&#20184;&#26631;&#35782;</th>
                        <th>&#25903;&#20184;&#22330;&#26223;</th>
                        <th>&#25903;&#20184;&#26041;&#24335;</th>
                        <th>&#22788;&#29702;&#36335;&#30001;</th>
                        <th>&#26159;&#21542;&#21551;&#29992;</th>
                        <th>&#26356;&#26032;&#26102;&#38388;</th>
                        <th>&#25805;&#20316;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ $payment->name }}</td>
                            <td>{{ $providerOptions[$payment->provider] ?? $payment->provider }}</td>
                            <td>{{ $payment->payment_mark ?: '-' }}</td>
                            <td>{{ $payment->sceneLabel() }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td class="payment-route-cell">{{ $payment->route_path }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.payments.toggle', $payment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="status-switch {{ $payment->is_enabled ? 'is-on' : '' }}">{{ $payment->enabledLabel() }}</button>
                                </form>
                            </td>
                            <td>{{ $payment->updated_at?->copy()?->timezone('Asia/Shanghai')?->format('Y-m-d H:i:s') ?? '' }}</td>
                            <td>
                                <a class="admin-link-button" href="{{ route('admin.payments.edit', $payment) }}" data-no-spa>&#32534;&#36753;</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="admin-table-empty">&#26242;&#26080;&#25903;&#20184;&#36890;&#36947;&#65292;&#28857;&#20987;&#21491;&#19978;&#35282;&#26032;&#22686;&#12290;</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
