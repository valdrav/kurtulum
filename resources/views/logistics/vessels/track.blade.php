@extends('layouts.app')
@section('title', __('logistics.vessel_tracking'))
@section('content')
@include('partials.page-header', ['title' => __('logistics.vessel_tracking')])

@if(empty($apiConfigured))
<div class="alert alert-info">
    <strong>Ücretsiz gemi takibi:</strong> <a href="https://marinesia.com/" target="_blank" rel="noopener">Marinesia</a> hesabı açıp ücretsiz API anahtarını
    <a href="{{ route('settings.marinetraffic') }}">Ayarlar → Gemi Takibi API</a> bölümüne girin.
    API olmadan da IMO ile arama yapabilirsiniz — harita VesselFinder üzerinden gösterilir.
</div>
@else
<div class="alert alert-success py-2 small">
    <i class="ti ti-ship"></i>
    @if($activeProvider === 'marinesia')
    Marinesia ücretsiz AIS bağlantısı aktif — IMO veya MMSI ile arayın.
    @else
    MarineTraffic AIS bağlantısı aktif.
    @endif
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('vessels.track.search') }}" class="row g-2">
            <div class="col-md-10">
                <input type="search" name="q" class="form-control form-control-lg" placeholder="IMO: 9375783 veya MMSI: 636019825" value="{{ $query ?? '' }}" required autofocus>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="ti ti-search me-1"></i> {{ __('app.search') }}</button>
            </div>
        </form>
        <p class="text-muted small mt-2 mb-0">Her IMO numarası için tek kayıt tutulur. MMSI ve gemi bilgileri otomatik tamamlanır.</p>
    </div>
</div>

@if(!empty($searchEmpty))
<div class="alert alert-warning">Gemi bulunamadı. IMO (7 hane) veya MMSI (9 hane) ile tekrar deneyin.</div>
@endif

@if(isset($results))
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">{{ __('app.search') }}: {{ $query }}</h3></div>
    <div class="list-group list-group-flush">
        @forelse($results as $v)
        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
            <a href="{{ route('vessels.track.show', $v) }}" class="text-body text-decoration-none flex-grow-1">
                <div class="fw-semibold">{{ $v->name }}</div>
                <div class="text-muted small">{{ $v->identifierLabel() }}</div>
                @if($v->vessel_type)<div class="text-muted small">{{ $v->vessel_type }} @if($v->flag_country)· {{ $v->flag_country }}@endif</div>@endif
            </a>
            <a href="{{ route('vessels.track.show', $v) }}" class="btn btn-sm btn-primary">{{ __('logistics.view_details') }}</a>
        </div>
        @empty
        <div class="list-group-item text-muted">{{ __('app.no_records') }}</div>
        @endforelse
    </div>
</div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ __('logistics.tracked_vessels') }}</h3>
        <span class="text-muted small">{{ $recentVessels->count() }} {{ __('logistics.vessel') }}</span>
    </div>
    <div class="list-group list-group-flush">
        @forelse($recentVessels as $v)
        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
            <a href="{{ route('vessels.track.show', $v) }}" class="text-body text-decoration-none flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-ship text-cyan"></i>
                    <div>
                        <strong>{{ $v->name }}</strong>
                        <div class="text-muted small">{{ $v->identifierLabel() }}</div>
                    </div>
                </div>
            </a>
            <form method="POST" action="{{ route('vessels.track.destroy', $v) }}" onsubmit="return confirm('{{ __('logistics.vessel_remove_confirm') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('logistics.remove_vessel') }}">
                    <i class="ti ti-trash"></i>
                </button>
            </form>
        </div>
        @empty
        <div class="p-4 text-muted">{{ __('logistics.no_tracked_vessels') }}</div>
        @endforelse
    </div>
</div>
@endsection
