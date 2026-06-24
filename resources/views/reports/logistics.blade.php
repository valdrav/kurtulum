@extends('layouts.app')
@section('title', __('reports.logistics_title'))
@section('content')
@include('partials.page-header', ['title' => __('reports.logistics_title')])
<a href="{{ route('reports.index') }}" class="btn btn-sm btn-ghost-secondary mb-3"><i class="ti ti-arrow-left"></i> {{ __('app.reports') }}</a>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('reports.total_shipments') }}</div>
            <div class="h2 mb-0">{{ $total }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">{{ __('reports.delayed_shipments') }}</div>
            <div class="h2 text-red mb-0">{{ $delayed }}</div>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('logistics.transport_mode') }}</h3></div>
            <div class="list-group list-group-flush">
                @forelse($byMode as $m)
                <div class="list-group-item d-flex justify-content-between">
                    <span>{{ __('logistics.' . $m->transport_mode) }}</span>
                    <strong>{{ $m->count }}</strong>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('app.status') }}</h3></div>
            <div class="list-group list-group-flush">
                @forelse($byStatus as $s)
                <div class="list-group-item d-flex justify-content-between">
                    <span>{{ status_label($s->status, 'shipment') }}</span>
                    <strong>{{ $s->count }}</strong>
                </div>
                @empty
                <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
