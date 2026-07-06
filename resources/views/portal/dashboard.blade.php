@extends('layouts.portal')
@section('title', __('portal.dashboard'))

@section('content')
<div class="row g-3 mb-3">
    @if(portal()->allows('orders'))
    <div class="col-6 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('portal.my_orders') }}</div><div class="h2 mb-0">{{ $stats['orders'] }}</div></div></div></div>
    @endif
    @if(portal()->allows('shipments'))
    <div class="col-6 col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('portal.my_shipments') }}</div><div class="h2 mb-0">{{ $stats['shipments'] }}</div></div></div></div>
    @endif
</div>

@if(portal()->allows('orders') && $recentOrders->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('portal.my_orders') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern mb-0">
            <thead><tr><th>No</th><th>Tarih</th><th>Tutar</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>
                @foreach($recentOrders as $order)
                <tr>
                    <td><a href="{{ route('portal.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td>{{ $order->order_date?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ format_money($order->total_amount, $order->currency) }}</td>
                    <td>{{ status_label($order->status, 'order') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(portal()->allows('shipments') && $recentShipments->isNotEmpty())
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('portal.my_shipments') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern mb-0">
            <thead><tr><th>No</th><th>Mod</th><th>{{ __('app.status') }}</th></tr></thead>
            <tbody>
                @foreach($recentShipments as $shipment)
                <tr>
                    <td><a href="{{ route('portal.shipments.show', $shipment) }}">{{ $shipment->shipment_number }}</a></td>
                    <td>{{ __('logistics.'.$shipment->transport_mode) }}</td>
                    <td>{{ $shipment->statusDisplay() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
