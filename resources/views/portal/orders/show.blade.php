@extends('layouts.portal')
@section('title', $order->order_number)

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Sipariş No</dt><dd class="col-sm-9">{{ $order->order_number }}</dd>
            <dt class="col-sm-3">Tarih</dt><dd class="col-sm-9">{{ $order->order_date?->format('d.m.Y') ?? '—' }}</dd>
            <dt class="col-sm-3">Teslimat</dt><dd class="col-sm-9">{{ $order->delivery_date?->format('d.m.Y') ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('app.status') }}</dt><dd class="col-sm-9">{{ status_label($order->status, 'order') }}</dd>
            <dt class="col-sm-3">Toplam</dt><dd class="col-sm-9 fw-bold">{{ format_money($order->total_amount, $order->currency) }}</dd>
        </dl>
    </div>
</div>
@if($order->items->isNotEmpty())
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Kalemler</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern mb-0">
            <thead><tr><th>Ürün</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name ?? $item->description ?? '—' }}</td>
                    <td>{{ $item->quantity }} {{ $item->unit ?? '' }}</td>
                    <td>{{ format_money($item->unit_price, $order->currency) }}</td>
                    <td>{{ format_money($item->total, $order->currency) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
