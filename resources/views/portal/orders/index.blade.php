@extends('layouts.portal')
@section('title', __('portal.my_orders'))

@section('content')
<div class="card mb-3"><div class="card-body py-3">
    <form method="GET" class="row g-2"><div class="col-md-10"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('app.search') }}"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div></form>
</div></div>
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table table-modern mb-0">
    <thead><tr><th>No</th><th>Tarih</th><th>Tutar</th><th>{{ __('app.status') }}</th></tr></thead>
    <tbody>
        @forelse($orders as $order)
        <tr>
            <td><a href="{{ route('portal.orders.show', $order) }}" class="fw-semibold">{{ $order->order_number }}</a></td>
            <td>{{ $order->order_date?->format('d.m.Y') ?? '—' }}</td>
            <td>{{ format_money($order->total_amount, $order->currency) }}</td>
            <td>{{ status_label($order->status, 'order') }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
        @endforelse
    </tbody>
</table></div>
@if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
</div>
@endsection
