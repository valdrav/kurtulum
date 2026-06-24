@extends('layouts.settings')
@section('settings-title', 'Gemi Takibi API')

@section('settings-content')
<div class="card mb-3 border-success">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="avatar bg-green-lt"><i class="ti ti-ship"></i></span>
            <div>
                <h3 class="mb-0">Marinesia — Ücretsiz API (Önerilen)</h3>
                <p class="text-muted mb-0 small">IMO veya MMSI ile canlı gemi konumu. Ücretsiz kayıt, kredi kartı gerekmez.</p>
            </div>
        </div>
        <ol class="small text-muted mb-0">
            <li><a href="https://marinesia.com/" target="_blank" rel="noopener">marinesia.com</a> adresinden ücretsiz hesap açın</li>
            <li>Panelden <strong>Free API Key</strong> oluşturun</li>
            <li>Anahtarı aşağıya yapıştırın — IMO numarası ile arama çalışır</li>
        </ol>
        <p class="small text-muted mt-2 mb-0">Ücretsiz planda saatte 1 sorgu limiti vardır; IMO/MMSI araması için yeterlidir.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('settings.marinetraffic.update') }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Marinesia API Anahtarı</label>
                <input type="password" name="marinesia_api_key" class="form-control font-monospace"
                    value="{{ old('marinesia_api_key', $settings['marinesia_api_key']) }}"
                    placeholder="Marinesia ücretsiz API key">
                <div class="form-text">Alternatif: `.env` dosyasına `MARINESIA_API_KEY=...` ekleyin.</div>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label text-muted">MarineTraffic API (isteğe bağlı, ücretli)</label>
                <input type="password" name="marinetraffic_api_key" class="form-control font-monospace"
                    value="{{ old('marinetraffic_api_key', $settings['marinetraffic_api_key']) }}"
                    placeholder="MarineTraffic API key (opsiyonel)">
            </div>
            @if($settings['configured'])
            <div class="alert alert-success py-2 small mb-3"><i class="ti ti-check"></i> Gemi takibi aktif ({{ $settings['provider'] === 'marinesia' ? 'Marinesia ücretsiz' : 'MarineTraffic' }}).</div>
            @else
            <div class="alert alert-info py-2 small mb-3">
                API anahtarı olmadan da IMO girildiğinde <strong>VesselFinder</strong> haritası gösterilir (ücretsiz gömülü harita).
            </div>
            @endif
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
            <a href="{{ route('vessels.track.index') }}" class="btn btn-outline-secondary ms-2">Gemi Takibini Dene</a>
        </form>
    </div>
</div>
@endsection
