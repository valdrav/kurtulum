@extends('layouts.app')
@section('title', __('app.tracking'))
@section('content')
@include('partials.page-header', ['title' => __('app.tracking')])
<div class="row row-cards">
    @forelse($shipments as $s)
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-status-top bg-{{ config('ticari.transport_modes.'.$s->transport_mode.'.color') }}"></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div><a href="{{ route('shipments.show', $s) }}" class="h3 mb-0">{{ $s->shipment_number }}</a>
                    <div class="text-muted">{{ $s->customer?->company_name }}</div></div>
                    <span class="badge bg-{{ config('ticari.transport_modes.'.$s->transport_mode.'.color') }}-lt"><i class="ti ti-{{ config('ticari.transport_modes.'.$s->transport_mode.'.icon') }}"></i></span>
                </div>
                <div class="progress progress-sm mb-2">
                    @php $done = $s->milestones->where('status','completed')->count(); $total = max($s->milestones->count(), 1); @endphp
                    <div class="progress-bar" style="width: {{ ($done/$total)*100 }}%"></div>
                </div>
                <div class="row text-center small">
                    <div class="col"><div class="text-muted">ETD</div>{{ $s->etd?->format('d.m') ?? '-' }}</div>
                    <div class="col"><div class="text-muted">ETA</div><strong class="{{ $s->eta && $s->eta->isPast() && !in_array($s->status,['delivered','completed']) ? 'text-danger' : '' }}">{{ $s->eta?->format('d.m') ?? '-' }}</strong></div>
                    <div class="col"><div class="text-muted">{{ __('app.status') }}</div><span class="fw-semibold">{{ $s->statusDisplay() }}</span></div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12"><div class="empty"><p class="empty-title">{{ __('app.no_records') }}</p></div></div>
    @endforelse
</div>
@endsection
