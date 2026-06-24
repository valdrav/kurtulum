@auth
@php
    $rateService = app(\App\Services\ExchangeRateService::class);
    $barRates = isset($navbarRates) ? $navbarRates : collect($rateService->ratesForBar())->keyBy('code');
    $barCodes = $rateService->barCurrencyCodes();
    $rateUpdatedLabel = $rateService->lastUpdatedLabel();
    $syncMinutes = $rateService->syncIntervalMinutes();
    $allCurrencies = registry()->currencies();
@endphp
<div class="ef-currency-bar" x-data="currencyBar({
    rates: @js($barRates->map(fn ($r) => ['code' => $r['code'], 'tcmb' => $r['tcmb_rate'], 'market' => $r['market_rate']])->values()),
    barCodes: @js($barCodes),
    updatedAt: @js($rateUpdatedLabel),
    syncMinutes: {{ $syncMinutes }},
})" :class="{ 'expanded': expanded }">
    <div class="ef-currency-bar-inner">
    <button type="button" class="ef-currency-toggle w-100" @click="expanded = !expanded" :aria-expanded="expanded">
        <span class="ef-currency-compact">
            <i class="ti ti-currency-lira"></i>
            <span class="ef-currency-compact-text" x-show="!expanded" x-text="compactSummary || @js(__('currencies.rates_title'))"></span>
            <span class="ef-currency-compact-meta" x-text="updatedAt">—</span>
        </span>
        <i class="ti ti-chevron-up ef-chevron" :class="{ 'rotated': expanded }"></i>
    </button>

    <div class="ef-currency-panel" x-show="expanded" x-cloak @click.stop>
        <div class="ef-currency-rates mb-2">
            @foreach($barCodes as $code)
            @php $row = $barRates->get($code); @endphp
            @if($row)
            <button type="button" class="ef-rate-chip ef-rate-chip-dual" @click="pick('{{ $code }}', {{ $row['tcmb_rate'] }})">
                <span class="ef-rate-code">{{ $code }}</span>
                <span class="ef-rate-name">{{ currency_name($code) }}</span>
                <span class="ef-rate-dual">
                    <span class="ef-rate-line"><small>MB</small> <strong data-tcmb="{{ $code }}">{{ number_format($row['tcmb_rate'], 4, ',', '.') }}</strong></span>
                    <span class="ef-rate-line"><small>SP</small> <strong data-market="{{ $code }}">{{ number_format($row['market_rate'], 4, ',', '.') }}</strong></span>
                </span>
            </button>
            @endif
            @endforeach
        </div>

        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <input type="number" step="0.01" class="form-control form-control-sm" x-model="amount" placeholder="{{ __('app.amount') }}">
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" x-model="from">
                    @foreach($allCurrencies as $c)<option value="{{ $c->code }}">{{ $c->code }}</option>@endforeach
                </select>
            </div>
            <div class="col-auto px-0 text-muted">→</div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" x-model="to">
                    @foreach($allCurrencies as $c)<option value="{{ $c->code }}">{{ $c->code }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" x-model="rateType">
                    <option value="tcmb">MB</option>
                    <option value="market">SP</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-primary w-100" @click="convert()">{{ __('currencies.convert') }}</button>
            </div>
            <div class="col-md-2"><div class="fw-bold small" x-text="result || '—'"></div></div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-ghost-secondary" @click="refreshRates(true)" :disabled="refreshing">
                <i class="ti ti-refresh" :class="{ 'ti-spin': refreshing }"></i>
                <span x-text="refreshing ? @js(__('currencies.refreshing')) : @js(__('currencies.refresh'))"></span>
            </button>
            <span class="text-muted small">{{ __('currencies.auto_refresh', ['minutes' => $syncMinutes]) }}</span>
            <span class="text-danger small" x-show="refreshError" x-text="refreshError"></span>
        </div>
    </div>
    </div>
</div>

@once
@push('scripts')
<script>
function currencyBar(config) {
    const rateMap = Object.fromEntries((config.rates || []).map(r => [r.code, { tcmb: r.tcmb, market: r.market }]));
    return {
        expanded: false,
        amount: 100,
        from: 'USD',
        to: 'TRY',
        rateType: 'tcmb',
        result: '',
        updatedAt: config.updatedAt || '—',
        compactSummary: (config.barCodes || []).map(code => {
            const r = rateMap[code];
            if (!r) return code;
            return code + ' ' + Number(r.tcmb).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }).join(' · '),
        refreshing: false,
        refreshError: '',
        syncMs: (config.syncMinutes || 15) * 60 * 1000,
        pick(code, tcmbRate) {
            this.from = code; this.to = 'TRY'; this.rateType = 'tcmb';
            if (rateMap[code]) rateMap[code].tcmb = tcmbRate;
            this.convert();
        },
        applyRates(rates, updatedAt) {
            (rates || []).forEach(r => {
                rateMap[r.code] = { tcmb: r.tcmb_rate, market: r.market_rate };
                const tcmbEl = document.querySelector('[data-tcmb="' + r.code + '"]');
                const mktEl = document.querySelector('[data-market="' + r.code + '"]');
                if (tcmbEl) tcmbEl.textContent = Number(r.tcmb_rate).toLocaleString('tr-TR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
                if (mktEl) mktEl.textContent = Number(r.market_rate).toLocaleString('tr-TR', { minimumFractionDigits: 4, maximumFractionDigits: 4 });
            });
            if (updatedAt) this.updatedAt = updatedAt;
            this.compactSummary = (config.barCodes || []).map(code => {
                const r = rateMap[code];
                if (!r) return code;
                return code + ' ' + Number(r.tcmb).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }).join(' · ');
        },
        async convert() {
            const res = await fetch('{{ route('exchange-rates.convert') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ amount: this.amount, from: this.from, to: this.to, rate_type: this.rateType }),
            });
            const data = await res.json();
            this.result = data.result != null
                ? Number(data.result).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) + ' ' + data.to
                : (data.error || '—');
        },
        async refreshRates(force = false) {
            if (this.refreshing) return;
            this.refreshing = true;
            this.refreshError = '';
            try {
                const url = '{{ route('exchange-rates.api') }}' + (force ? '?force=1' : '');
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Kur güncellenemedi');
                this.applyRates(data.rates, data.updated_at);
            } catch (e) {
                this.refreshError = e.message || 'Bağlantı hatası';
            } finally {
                this.refreshing = false;
            }
        },
        init() {
            setInterval(() => this.refreshRates(true), this.syncMs);
        }
    }
}
</script>
@endpush
@endonce
@endauth
