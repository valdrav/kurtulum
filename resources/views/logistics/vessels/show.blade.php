@extends('layouts.app')
@section('title', $vessel->name)
@section('content')
@php
    $meta = is_array($liveMeta ?? null) ? $liveMeta : ($position?->meta ?? []);
    $vp = $voyagePlan ?? [];
    $dest = $vp['next_port'] ?? port_display_label($meta['destination'] ?? null);
    $eta = $vp['ais_eta_label'] ?? $meta['eta'] ?? null;
    $navStatus = $vp['nav_status'] ?? $meta['status'] ?? null;
    $navStatusDisplay = vessel_nav_status_display($navStatus);
    $isLive = in_array($source ?? '', ['marinesia', 'marinetraffic'], true);
    $isPublicMeta = ($source ?? '') === 'vesselfinder';
    $isEstimate = ($positionEstimated ?? false) || in_array($source ?? '', ['public_estimate', 'estimated'], true);
    $mapLat = $position?->latitude ?? 39.0;
    $mapLng = $position?->longitude ?? 35.0;
    $mapZoom = $position ? 7 : 5;
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <a href="{{ route('vessels.track.index') }}" class="btn btn-ghost-secondary btn-sm mb-2"><i class="ti ti-arrow-left"></i> {{ __('logistics.vessel_tracking') }}</a>
        <h2 class="page-title mb-0"><i class="ti ti-ship text-cyan"></i> {{ $vessel->name }}</h2>
        <div class="text-muted small">{{ $vessel->identifierLabel() }}</div>
        @if($vessel->vessel_type || $vessel->flag_country)
        <div class="text-muted small">{{ $vessel->vessel_type }}@if($vessel->vessel_type && $vessel->flag_country) · @endif{{ $vessel->flag_country }}</div>
        @endif
    </div>
    @if($isLive)
    <span class="badge bg-green-lt">{{ __('logistics.vessel_position_live') }}</span>
    @elseif($position && ($fromCache ?? false))
    <span class="badge bg-azure-lt">{{ __('logistics.cached_position') }} ({{ $position->recorded_at->diffForHumans() }})</span>
    @elseif($position && in_array($source, ['estimated', 'public_estimate'], true))
    <span class="badge bg-orange-lt">{{ __('logistics.estimated_position') }}</span>
    @elseif($isPublicMeta)
    <span class="badge bg-cyan-lt">{{ __('logistics.voyage_info') }}</span>
    @else
    <span class="badge bg-secondary-lt">{{ __('logistics.waiting_position') }}</span>
    @endif
    @if($apiConfigured)
    <a href="{{ route('vessels.track.show', [$vessel, 'refresh' => 1]) }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-refresh"></i> {{ __('logistics.refresh_position') }}
    </a>
    @endif
    <form method="POST" action="{{ route('vessels.track.destroy', $vessel) }}" class="d-inline"
          data-confirm="{{ __('logistics.vessel_remove_confirm') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i> {{ __('logistics.remove_vessel') }}</button>
    </form>
</div>

@if(!$apiConfigured && $vessel->imo_number)
<div class="alert alert-info py-2 small">
    Canlı konum için ücretsiz <a href="https://marinesia.com/" target="_blank" rel="noopener">Marinesia</a> API anahtarını
    <a href="{{ route('settings.marinetraffic') }}">Ayarlar → Gemi Takibi API</a> bölümüne ekleyin.
    Harita sistem içinde OpenStreetMap ile gösterilir.
</div>
@elseif($apiConfigured && !$position && $isPublicMeta)
<div class="alert alert-warning py-2 small">
    Marinesia istek limitine ulaşıldı veya anlık AIS sinyali yok. Sefer özeti VesselFinder üzerinden gösteriliyor.
    Birkaç dakika sonra <strong>Konumu yenile</strong> ile tekrar deneyin.
</div>
@elseif($apiConfigured && !$position)
<div class="alert alert-warning py-2 small">
    Canlı AIS konumu alınamadı. Gemi karada olabilir veya sinyal gecikmiş olabilir. <strong>Konumu yenile</strong> ile tekrar deneyin.
</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ __('logistics.vessel_map') }}</h3>
                @if($source === 'marinesia')
                <span class="badge bg-green-lt">Marinesia AIS</span>
                @elseif($source === 'marinetraffic')
                <span class="badge bg-cyan-lt">MarineTraffic</span>
                @elseif($source === 'vesselfinder')
                <span class="badge bg-cyan-lt">VesselFinder özeti</span>
                @else
                <span class="badge bg-secondary-lt">OpenStreetMap</span>
                @endif
            </div>
            <div class="card-body p-0 position-relative">
                <div id="vesselMap" style="height:460px;min-height:300px"></div>
                @unless($position)
                <div class="ef-map-overlay small text-muted">
                    @if($apiConfigured)
                    Canlı AIS sinyali alınamadı — «Konumu yenile» ile tekrar deneyin.
                    @else
                    Marinesia API ile canlı konum gösterilir.
                    @endif
                </div>
                @elseif($isEstimate)
                <div class="ef-map-overlay small text-warning">
                    Tahmini konum — Marinesia limiti veya sinyal gecikmesi nedeniyle rota üzerinden hesaplandı.
                </div>
                @endunless
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-id"></i> {{ __('logistics.vessel_identity') }}</h3></div>
            <div class="card-body">
                <dl class="mb-0 small">
                    <dt>{{ bilingual_field_label('logistics.imo_number', 'IMO') }}</dt><dd class="fw-semibold">{{ $vessel->imo_number ?? '—' }}</dd>
                    <dt>{{ bilingual_field_label('logistics.mmsi_number', 'MMSI') }}</dt><dd class="fw-semibold">{{ $vessel->mmsi ?? '—' }}</dd>
                    <dt>{{ bilingual_field_label('logistics.callsign', 'Call sign') }}</dt><dd>{{ $vessel->callsign ?? '—' }}</dd>
                    <dt>{{ bilingual_field_label('logistics.flag', 'Flag') }}</dt><dd>{{ $vessel->flag_country ?? '—' }}</dd>
                    <dt>{{ bilingual_field_label('logistics.ship_type', 'Ship type') }}</dt><dd>{{ $vessel->vessel_type ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-ruler"></i> {{ __('logistics.vessel_specs') }}</h3></div>
            <div class="card-body">
                <dl class="mb-0 small">
                    <dt>{{ __('logistics.gross_tonnage') }}</dt><dd>{{ $vessel->gt ? number_format((float) $vessel->gt, 0, ',', '.') . ' t' : '—' }}</dd>
                    <dt>{{ __('logistics.deadweight') }}</dt><dd>{{ $vessel->dwt ? number_format((float) $vessel->dwt, 0, ',', '.') . ' t' : '—' }}</dd>
                    <dt>{{ __('logistics.length_overall') }}</dt><dd>{{ $vessel->length_m ? $vessel->length_m . ' m' : '—' }}</dd>
                    <dt>{{ __('logistics.beam') }}</dt><dd>{{ $vessel->beam_m ? $vessel->beam_m . ' m' : '—' }}</dd>
                    <dt>{{ __('logistics.year_built') }}</dt><dd>{{ $vessel->year_built ?? '—' }}</dd>
                    @if($vp['draught'] ?? null)<dt>{{ __('logistics.draught') }}</dt><dd>{{ $vp['draught'] }} m</dd>@endif
                </dl>
            </div>
        </div>

        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary-lt"><h3 class="card-title mb-0"><i class="ti ti-route"></i> {{ __('logistics.voyage_plan') }}</h3></div>
            <div class="card-body">
                @if(($vp['origin_port'] ?? null) && ($dest ?? null))
                <div class="alert alert-light py-2 small mb-3">
                    <strong>{{ __('logistics.route_summary') }}:</strong>
                    {{ $vp['origin_port'] }} → {{ $dest }}
                    @if($eta) · {{ __('logistics.ais_eta') }}: {{ $eta }}@endif
                </div>
                @endif
                <dl class="mb-0 small">
                    @if($navStatus)<dt>{{ bilingual_field_label('logistics.nav_status', 'Nav status') }}</dt><dd>{{ $navStatusDisplay }}</dd>@endif
                    @if($vp['origin_port'] ?? null)<dt>{{ __('logistics.departure_port') }}</dt><dd>{{ $vp['origin_port'] }}</dd>@endif
                    @if($dest)<dt>{{ __('logistics.arrival_port') }}</dt><dd class="fw-semibold text-primary">{{ $dest }}</dd>@endif
                    @if(($vp['ais_destination'] ?? null) && ($vp['ais_destination'] !== $dest))<dt>{{ __('logistics.ais_destination') }}</dt><dd>{{ $vp['ais_destination'] }}</dd>@endif
                    @if($eta)<dt>{{ __('logistics.ais_eta') }}</dt><dd class="fw-semibold">{{ $eta }}</dd>@endif
                    @if($vp['planned_eta_label'] ?? null)<dt>{{ __('logistics.planned_eta') }}</dt><dd>{{ $vp['planned_eta_label'] }}</dd>@endif
                    @if($vp['planned_etd_label'] ?? null)<dt>{{ __('logistics.planned_etd') }}</dt><dd>{{ $vp['planned_etd_label'] }}</dd>@endif
                    @if($vp['draught'] ?? null)<dt>{{ __('logistics.draught') }}</dt><dd>{{ $vp['draught'] }} m</dd>@endif
                    @if($vp['shipment_number'] ?? null)
                    <dt>{{ __('logistics.active_shipment') }}</dt>
                    <dd>
                        <a href="{{ route('shipments.show', $activeShipment) }}">{{ $vp['shipment_number'] }}</a>
                        @if($vp['shipment_status'])<span class="badge bg-secondary-lt ms-1">{{ status_label($vp['shipment_status'], 'shipment') }}</span>@endif
                    </dd>
                    @endif
                    @if($vp['bl_number'] ?? null)<dt>B/L No</dt><dd>{{ $vp['bl_number'] }}</dd>@endif
                    @if($vp['voyage_number'] ?? null)<dt>Sefer No</dt><dd>{{ $vp['voyage_number'] }}</dd>@endif
                </dl>
                @if(empty(array_filter($vp)))
                <p class="text-muted small mb-0">{{ __('logistics.no_voyage_data') }}</p>
                @endif
            </div>
        </div>

        @if($position || !empty($meta))
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-antenna"></i> {{ __('logistics.vessel_live_ais') }}</h3></div>
            <div class="card-body">
                <dl class="mb-0 small">
                    @if($navStatus)<dt>{{ bilingual_field_label('logistics.nav_status', 'Nav status') }}</dt><dd>{{ $navStatusDisplay }}</dd>@endif
                    @if($meta['last_port'] ?? null)<dt>{{ __('logistics.last_port') }}</dt><dd>{{ port_display_label($meta['last_port']) }}</dd>@endif
                    @if($dest)<dt>{{ __('logistics.destination') }}</dt><dd>{{ $dest }}</dd>@endif
                    @if($eta)<dt>{{ __('logistics.ais_eta') }}</dt><dd>{{ $eta }}</dd>@endif
                    @if($position?->speed)<dt>{{ __('logistics.speed') }}</dt><dd>{{ number_format($position->speed, 1) }} kn</dd>@endif
                    @if($position?->course)<dt>{{ __('logistics.course') }}</dt><dd>{{ number_format($position->course, 0) }}°</dd>@endif
                    @if($position)<dt>{{ __('logistics.position') }}</dt><dd>{{ number_format($position->latitude, 5) }}, {{ number_format($position->longitude, 5) }}</dd>@endif
                    @if($position)<dt>{{ __('logistics.last_signal') }}</dt><dd>{{ $position->recorded_at->format('d.m.Y H:i') }}</dd>@endif
                    <dt>{{ __('logistics.data_source') }}</dt><dd><span class="badge bg-secondary-lt">{{ $source ?? '—' }}</span></dd>
                </dl>
            </div>
        </div>
        @endif

        @if($history->count() > 1)
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('logistics.position_history') }}</h3></div>
            <div class="list-group list-group-flush">
                @foreach($history as $p)
                <div class="list-group-item small">
                    {{ $p->recorded_at->format('d.m.Y H:i') }}
                    — {{ number_format($p->latitude, 4) }}, {{ number_format($p->longitude, 4) }}
                    <span class="badge bg-secondary-lt">{{ $p->source }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.vessel-marker-icon { font-size: 28px; line-height: 1; filter: drop-shadow(0 2px 4px rgba(0,0,0,.25)); }
.ef-map-overlay {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(255,255,255,.88); padding: .5rem 1rem;
    border-top: 1px solid var(--ef-border);
}
[data-bs-theme="dark"] .ef-map-overlay { background: rgba(26,31,46,.9); }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hasPosition = {{ $position ? 'true' : 'false' }};
    const lat = {{ $mapLat }};
    const lng = {{ $mapLng }};
    const zoom = {{ $mapZoom }};
    const mapLocale = @json(app()->getLocale());
    const attributions = {
        tr: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> katkıda bulunanlar',
        en: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        ar: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        de: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        fr: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    };

    const map = L.map('vesselMap', { zoomControl: true }).setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: attributions[mapLocale] || attributions.en
    }).addTo(map);

    if (hasPosition) {
        const shipIcon = L.divIcon({
            html: '<div class="vessel-marker-icon">🚢</div>',
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
        L.marker([lat, lng], { icon: shipIcon }).addTo(map)
            .bindPopup(@json($vessel->name))
            .openPopup();
        @if($position && $position->course)
        const course = {{ (float) $position->course }};
        const len = 0.08;
        const rad = (90 - course) * Math.PI / 180;
        L.polyline([[lat, lng], [lat + len * Math.sin(rad), lng + len * Math.cos(rad)]], { color: '#6366f1', weight: 3 }).addTo(map);
        @endif
        @if($history->count() > 1)
        const path = @json($history->map(fn($p) => [(float)$p->latitude, (float)$p->longitude])->reverse()->values());
        L.polyline(path, { color: '#06b6d4', weight: 2, opacity: 0.6, dashArray: '6 4' }).addTo(map);
        if (path.length > 1) map.fitBounds(path, { padding: [40, 40] });
        @endif
    }
});
</script>
@endpush
