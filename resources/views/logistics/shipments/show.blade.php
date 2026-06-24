@extends('layouts.app')
@section('title', $shipment->shipment_number)
@section('content')
@include('partials.page-header', ['title' => $shipment->shipment_number])

<div class="row g-3">
    <div class="col-lg-8">
        @include('logistics.shipments._status-panel')

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('logistics.title') }}</h3>
                <div>
                    @if($shipment->vessel)
                    <a href="{{ route('vessels.track.show', $shipment->vessel) }}" class="btn btn-sm btn-outline-cyan me-1"><i class="ti ti-map"></i> {{ __('logistics.vessel_tracking') }}</a>
                    @endif
                    <a href="{{ route('shipments.edit', $shipment) }}" class="btn btn-sm btn-primary">{{ __('app.edit') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <span class="badge bg-{{ config('ticari.transport_modes.'.$shipment->transport_mode.'.color', 'secondary') }}-lt fs-4 mb-3">
                            <i class="ti ti-{{ config('ticari.transport_modes.'.$shipment->transport_mode.'.icon', 'truck') }}"></i>
                            {{ __('logistics.'.$shipment->transport_mode) }}
                        </span>
                        <span class="badge ms-2">{{ $shipment->statusDisplay() }}</span>
                        <dl class="row mt-2 mb-0">
                            <dt class="col-5">{{ __('logistics.origin') }}</dt><dd class="col-7">{{ port_display_label($shipment->origin, $shipment->originPort) ?? '-' }}</dd>
                            <dt class="col-5">{{ __('logistics.destination') }}</dt><dd class="col-7">{{ port_display_label($shipment->destination, $shipment->destinationPort) ?? '-' }}</dd>
                            @if($shipment->incoterm)
                            <dt class="col-5">Incoterm</dt><dd class="col-7">{{ incoterm_label($shipment->incoterm) }}</dd>
                            @endif
                            @if($shipment->bl_number)<dt class="col-5">{{ __('logistics.bl_number') }}</dt><dd class="col-7">{{ $shipment->bl_number }}</dd>@endif
                            @if($shipment->cmr_number)<dt class="col-5">{{ __('logistics.cmr_number') }}</dt><dd class="col-7">{{ $shipment->cmr_number }}</dd>@endif
                            @if($shipment->vessel)
                            <dt class="col-5">{{ __('logistics.vessel') }}</dt>
                            <dd class="col-7"><a href="{{ route('vessels.track.show', $shipment->vessel) }}">{{ $shipment->vessel->name }}</a></dd>
                            @endif
                            @if($shipment->vehicle)<dt class="col-5">Araç</dt><dd class="col-7">{{ $shipment->vehicle->plate_number ?? '-' }}</dd>@endif
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2">
                            <div class="col-6"><div class="border rounded p-2 text-center"><div class="text-muted small">{{ __('logistics.etd') }}</div><strong>{{ $shipment->etd?->format('d.m.Y') ?? '-' }}</strong></div></div>
                            <div class="col-6"><div class="border rounded p-2 text-center"><div class="text-muted small">{{ __('logistics.eta') }}</div><strong>{{ $shipment->eta?->format('d.m.Y') ?? '-' }}</strong></div></div>
                            <div class="col-6"><div class="border rounded p-2 text-center"><div class="text-muted small">{{ __('logistics.atd') }}</div><strong>{{ $shipment->atd?->format('d.m.Y') ?? '-' }}</strong></div></div>
                            <div class="col-6"><div class="border rounded p-2 text-center"><div class="text-muted small">{{ __('logistics.ata') }}</div><strong>{{ $shipment->ata?->format('d.m.Y') ?? '-' }}</strong></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($shipment->legs->count())
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Multimodal</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead><tr><th>#</th><th>Mod</th><th>{{ __('logistics.origin') }}</th><th>{{ __('logistics.destination') }}</th><th>{{ __('logistics.eta') }}</th><th>{{ __('app.status') }}</th></tr></thead>
                    <tbody>
                        @foreach($shipment->legs as $leg)
                        <tr>
                            <td>{{ $leg->leg_order ?? $leg->sequence ?? $loop->iteration }}</td>
                            <td>{{ __('logistics.'.$leg->transport_mode) }}</td>
                            <td>{{ $leg->origin }}</td>
                            <td>{{ $leg->destination }}</td>
                            <td>{{ $leg->eta?->format('d.m.Y') ?? '-' }}</td>
                            <td>{{ status_label($leg->status ?? 'pending', 'leg') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Milestones</h3></div>
            <div class="list-group list-group-flush">
                @foreach($shipment->milestones as $m)
                <div class="list-group-item d-flex align-items-center gap-2">
                    <span class="badge bg-{{ ($m->status ?? 'pending') === 'completed' ? 'success' : 'secondary' }}-lt"><i class="ti ti-{{ ($m->status ?? '') === 'completed' ? 'check' : 'clock' }}"></i></span>
                    <div>
                        <div>{{ $m->title ?? $m->name }}</div>
                        @if($m->completed_at)<small class="text-muted">{{ $m->completed_at->format('d.m.Y H:i') }}</small>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">{{ __('logistics.costs') }}</h3></div>
            <div class="card-body">
                @forelse($shipment->costs as $cost)
                <div class="d-flex justify-content-between mb-2"><span>{{ $cost->description }}</span><strong>{{ number_format($cost->amount,2) }} {{ $cost->currency }}</strong></div>
                @empty
                <p class="text-muted mb-0">{{ __('app.no_records') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
