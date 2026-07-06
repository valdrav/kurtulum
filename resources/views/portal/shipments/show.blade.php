@extends('layouts.portal')
@section('title', $shipment->shipment_number)

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Sevkiyat No</dt><dd class="col-sm-9">{{ $shipment->shipment_number }}</dd>
            <dt class="col-sm-3">Mod</dt><dd class="col-sm-9">{{ __('logistics.'.$shipment->transport_mode) }}</dd>
            <dt class="col-sm-3">{{ __('logistics.origin') }}</dt><dd class="col-sm-9">{{ $shipment->origin ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('logistics.destination') }}</dt><dd class="col-sm-9">{{ $shipment->destination ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('logistics.atd') }}</dt><dd class="col-sm-9">{{ $shipment->atd?->format('d.m.Y H:i') ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('logistics.ata') }}</dt><dd class="col-sm-9">{{ $shipment->ata?->format('d.m.Y H:i') ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('app.status') }}</dt><dd class="col-sm-9">{{ $shipment->statusDisplay() }}</dd>
            @if($showCosts)
            <dt class="col-sm-3">{{ __('portal.total_cost') }}</dt><dd class="col-sm-9 fw-bold">{{ format_money($shipment->total_cost, $shipment->currency ?? 'USD') }}</dd>
            @endif
        </dl>
    </div>
</div>

@if($showCosts)
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('portal.shipment_costs') }}</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-modern mb-0">
            <thead><tr><th>Kalem</th><th>Tarih</th><th>Tutar</th><th>Durum</th></tr></thead>
            <tbody>
                @forelse($shipment->costs as $cost)
                <tr>
                    <td>{{ $cost->item_name ?? $cost->description ?? $cost->type }}</td>
                    <td>{{ $cost->expense_date?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ format_money($cost->amount, $cost->currency) }}</td>
                    <td>{{ $cost->status ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('portal.no_costs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
