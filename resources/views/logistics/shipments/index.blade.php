@extends('layouts.app')
@section('title', __('app.shipments'))
@section('content')
@include('partials.page-header', ['title' => __('app.shipments'), 'createRoute' => route('shipments.create'), 'createPermission' => 'shipments.create'])

<div class="card mb-3">
    <div class="card-body py-3">
        <form class="row g-2" method="GET">
            <div class="col-12 col-md"><input type="text" name="search" class="form-control" placeholder="{{ __('app.search') }}" value="{{ request('search') }}"></div>
            <div class="col-6 col-md-auto"><select name="mode" class="form-select"><option value="">{{ __('logistics.transport_mode') }}</option>@foreach(['road','sea','air','rail','multimodal'] as $m)<option value="{{ $m }}" @selected(request('mode')===$m)>{{ __('logistics.'.$m) }}</option>@endforeach</select></div>
            <div class="col-6 col-md-auto"><select name="status" class="form-select"><option value="">{{ __('app.status') }}</option>@foreach(config('ticari.shipment_statuses') as $st)<option value="{{ $st }}" @selected(request('status')===$st)>{{ status_label($st, 'shipment') }}</option>@endforeach</select></div>
            <div class="col-12 col-md-auto"><button class="btn btn-outline-primary w-100">{{ __('app.filter') }}</button></div>
        </form>
    </div>
</div>

<div class="d-md-none ef-mobile-list mb-3">
    @forelse($shipments as $s)
    @include('partials.mobile-record-card', [
        'url' => route('shipments.show', $s),
        'title' => $s->shipment_number,
        'subtitle' => $s->customer?->company_name ?? '—',
        'meta' => __('logistics.'.$s->transport_mode).' · ETA: '.($s->eta?->format('d.m.Y') ?? '—'),
        'badge' => $s->statusDisplay(),
        'editUrl' => route('shipments.edit', $s),
        'editPermission' => 'shipments.edit',
    ])
    @empty
    <div class="card"><div class="card-body text-muted">{{ __('app.no_records') }}</div></div>
    @endforelse
</div>

<div class="card hide-mobile">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped table-modern">
            <thead><tr><th>No</th><th>Mod</th><th>{{ __('app.customers') }}</th><th>{{ __('logistics.etd') }}</th><th>{{ __('logistics.eta') }}</th><th>{{ __('app.status') }}</th><th></th></tr></thead>
            <tbody>
                @forelse($shipments as $s)
                <tr>
                    <td><a href="{{ route('shipments.show', $s) }}" class="fw-bold">{{ $s->shipment_number }}</a>
                        @if($s->bl_number)<div class="text-muted small">B/L: {{ $s->bl_number }}</div>@endif
                    </td>
                    <td><span class="badge bg-{{ config('ticari.transport_modes.'.$s->transport_mode.'.color') }}-lt">{{ __('logistics.'.$s->transport_mode) }}</span></td>
                    <td>{{ $s->customer?->company_name ?? '-' }}</td>
                    <td>{{ $s->etd?->format('d.m.Y H:i') ?? '-' }}</td>
                    <td>{{ $s->eta?->format('d.m.Y H:i') ?? '-' }}</td>
                    <td><span class="badge">{{ $s->statusDisplay() }}</span></td>
                    <td>
                        @if(can_access('shipments.edit'))
                        <a href="{{ route('shipments.edit', $s) }}" class="btn btn-sm btn-ghost-primary"><i class="ti ti-edit"></i></a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($shipments->hasPages())<div class="card-footer d-flex justify-content-center">{{ $shipments->links() }}</div>@endif
</div>
@if($shipments->hasPages())<div class="d-md-none mt-2">{{ $shipments->links() }}</div>@endif
@endsection
