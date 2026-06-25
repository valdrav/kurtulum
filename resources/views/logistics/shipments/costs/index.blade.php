@extends('layouts.app')
@section('title', __('logistics.shipment_costs'))
@section('content')
@include('partials.page-header', ['title' => __('logistics.shipment_costs'), 'subtitle' => __('logistics.shipment_costs_subtitle')])

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('shipments.index') }}" class="btn btn-ghost-secondary btn-sm"><i class="ti ti-arrow-left"></i> {{ __('app.shipments') }}</a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="GET" class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">{{ __('app.shipments') }}</label>
                <select name="shipment" class="form-select form-select-sm">
                    <option value="">{{ __('logistics.cost_all_shipments') }}</option>
                    @foreach($shipments as $s)
                    <option value="{{ $s->uuid }}" @selected($selectedShipment?->uuid === $s->uuid)>{{ $s->displayLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ __('logistics.cost_invoice') }}</label>
                <input type="text" name="invoice" class="form-control form-control-sm" value="{{ request('invoice') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ __('logistics.cost_status_label') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('app.all') ?? 'Tümü' }}</option>
                    @foreach(app(\App\Services\ShipmentCostService::class)->statusOptions() as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ __('finance.fiscal_year') }}</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">{{ __('app.all') ?? 'Tümü' }}</option>
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                    <option value="{{ $y }}" @selected((int) request('year') === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ __('app.search') }}</label>
                <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">{{ __('app.filter') }}</button>
            </div>
        </div>
    </div>
</form>

@if($totalsByCurrency->isNotEmpty())
<div class="row row-cards mb-3">
    @foreach($totalsByCurrency as $currency => $total)
    <div class="col-auto">
        <div class="card stat-card"><div class="card-body py-2 px-3">
            <div class="subheader small">{{ __('logistics.cost_total') }} ({{ $currency }})</div>
            <div class="h3 mb-0">{{ number_format($total, 2, ',', '.') }} {{ $currency }}</div>
        </div></div>
    </div>
    @endforeach
</div>
@endif

<div class="row g-3">
    @if(can_access('shipments.create'))
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('logistics.cost_add') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('shipments.costs.store-from-index') }}">
                    @csrf
                    @include('logistics.shipments.costs._form', [
                        'shipments' => $shipments,
                        'shipment' => $selectedShipment,
                        'compact' => true,
                        'redirect' => 'index',
                    ])
                    <button type="submit" class="btn btn-primary w-100 mt-2">{{ __('app.save') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="col-lg-{{ can_access('shipments.create') ? '8' : '12' }}">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('logistics.shipment_costs') }}</h3>
                @if($selectedShipment)
                <a href="{{ route('shipments.show', $selectedShipment) }}" class="btn btn-sm btn-ghost-secondary">{{ __('logistics.view_details') }}</a>
                @endif
            </div>
            @include('logistics.shipments.costs._table', [
                'items' => $items,
                'showShipmentColumn' => ! $selectedShipment,
                'redirect' => 'index',
            ])
            @if($items->hasPages())
            <div class="card-footer">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
