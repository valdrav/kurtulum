<?php

namespace App\Services;

use App\Models\SystemCurrency;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function lastUpdated(): ?\DateTimeInterface
    {
        $cached = Cache::get('exchange_rates.last_sync');
        $cachedTime = $cached ? \Carbon\Carbon::parse($cached) : null;

        $fromDb = SystemCurrency::query()
            ->where('is_active', true)
            ->whereNotNull('rate_updated_at')
            ->orderByDesc('rate_updated_at')
            ->value('rate_updated_at');

        $dbTime = $fromDb ? \Carbon\Carbon::parse($fromDb) : null;

        if ($cachedTime && $dbTime) {
            return $cachedTime->gt($dbTime) ? $cachedTime : $dbTime;
        }

        return $dbTime ?? $cachedTime;
    }

    public function lastUpdatedLabel(): string
    {
        $updated = $this->lastUpdated();

        if (! $updated) {
            return '—';
        }

        return \Carbon\Carbon::parse($updated)
            ->timezone(app_timezone())
            ->format('d.m.Y H:i');
    }

    public function syncIntervalMinutes(): int
    {
        return (int) config('ticari.exchange_rates.auto_sync_minutes', 15);
    }

    public function needsSync(?int $maxAgeMinutes = null): bool
    {
        $maxAgeMinutes ??= $this->syncIntervalMinutes();

        $last = $this->lastUpdated();

        if (! $last) {
            return true;
        }

        return now()->diffInMinutes($last) >= $maxAgeMinutes;
    }

    public function sync(bool $force = false): array
    {
        if (! $force && ! $this->needsSync()) {
            return ['updated' => 0, 'skipped' => true, 'source' => 'cache'];
        }

        $tcmb = $this->fetchFromTcmb();
        $market = $this->fetchFromGenelPara();

        if ($tcmb === [] && $market === []) {
            $fallback = $this->fetchFromFrankfurter();

            if ($fallback === []) {
                throw new \RuntimeException('Kur kaynağına ulaşılamadı (TCMB / GenelPara).');
            }

            $tcmb = $fallback;
        }

        $updated = 0;
        $now = now();

        SystemCurrency::where('code', 'TRY')->update([
            'exchange_rate' => 1,
            'tcmb_rate' => 1,
            'market_rate' => 1,
            'rate_updated_at' => $now,
        ]);

        foreach (SystemCurrency::where('is_active', true)->where('code', '!=', 'TRY')->get() as $currency) {
            $code = $currency->code;
            $tcmbRate = $tcmb[$code] ?? null;
            $marketRate = $market[$code] ?? null;

            if ($tcmbRate === null && $marketRate === null) {
                continue;
            }

            $primary = $tcmbRate ?? $marketRate;

            $currency->update([
                'exchange_rate' => $primary,
                'tcmb_rate' => $tcmbRate,
                'market_rate' => $marketRate ?? $tcmbRate,
                'rate_updated_at' => $now,
            ]);
            $updated++;
        }

        Cache::forget('registry.currencies.active');
        Cache::forget('registry.currencies.all');
        Cache::put('exchange_rates.last_sync', $now->toIso8601String(), 86400);

        return [
            'updated' => $updated,
            'skipped' => false,
            'source' => $tcmb !== [] ? 'tcmb+genelpara' : 'fallback',
        ];
    }

    /**
     * @return array<string, float>
     */
    protected function fetchFromTcmb(): array
    {
        $urls = [
            'https://www.tcmb.gov.tr/kurlar/today.xml',
        ];

        for ($i = 0; $i <= 5; $i++) {
            $date = now()->subDays($i)->format('Ymd');
            $urls[] = "https://www.tcmb.gov.tr/kurlar/{$date}.xml";
        }

        foreach (array_unique($urls) as $url) {
            $rates = $this->parseTcmbXml($url);

            if ($rates !== []) {
                return $rates;
            }
        }

        return [];
    }

    /**
     * @return array<string, float>
     */
    protected function parseTcmbXml(string $url): array
    {
        try {
            $response = $this->client()->get($url);

            if (! $response->successful()) {
                return [];
            }

            $xml = @simplexml_load_string($response->body());

            if (! $xml) {
                return [];
            }

            $rates = [];

            foreach ($xml->Currency as $node) {
                $code = (string) $node['CurrencyCode'];
                $unit = max(1, (int) ($node->Unit ?? 1));
                $forexSell = $this->parseNumber((string) $node->ForexSelling);
                $forexBuy = $this->parseNumber((string) $node->ForexBuying);
                $value = $forexSell > 0 ? $forexSell : $forexBuy;

                if ($code && $value > 0) {
                    $rates[$code] = round($value / $unit, 6);
                }
            }

            return $rates;
        } catch (\Throwable $e) {
            Log::warning("TCMB kur ({$url}): " . $e->getMessage());

            return [];
        }
    }

    /**
     * Serbest piyasa satış kurları (GenelPara).
     *
     * @return array<string, float>
     */
    protected function fetchFromGenelPara(): array
    {
        $symbols = implode(',', config('ticari.bar_currencies', ['USD', 'EUR', 'SAR']));

        try {
            $response = $this->client()->get('https://api.genelpara.com/json/', [
                'list' => 'doviz',
                'sembol' => $symbols,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json();

            if (! ($payload['success'] ?? false) || ! is_array($payload['data'] ?? null)) {
                return [];
            }

            $rates = [];

            foreach ($payload['data'] as $code => $row) {
                $sell = $this->parseNumber($row['satis'] ?? null);

                if ($sell > 0) {
                    $rates[strtoupper($code)] = round($sell, 6);
                }
            }

            return $rates;
        } catch (\Throwable $e) {
            Log::warning('GenelPara kur: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array<string, float>
     */
    protected function fetchFromFrankfurter(): array
    {
        try {
            $response = $this->client()->get('https://api.frankfurter.app/latest', [
                'from' => 'TRY',
            ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json('rates', []);
            $rates = [];

            foreach ($data as $code => $tryPerUnit) {
                if ($tryPerUnit > 0) {
                    $rates[$code] = round(1 / $tryPerUnit, 6);
                }
            }

            return $rates;
        } catch (\Throwable $e) {
            Log::warning('Frankfurter kur: ' . $e->getMessage());

            return [];
        }
    }

    protected function parseNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    protected function client(): PendingRequest
    {
        return Http::timeout(25)
            ->withHeaders(['User-Agent' => 'KurtulumERP/1.0'])
            ->when(! http_verify_ssl(), fn (PendingRequest $request) => $request->withOptions(['verify' => false]));
    }

    public function convert(float $amount, string $from, string $to, string $rateType = 'tcmb'): ?float
    {
        $column = $rateType === 'market' ? 'market_rate' : 'tcmb_rate';
        $currencies = SystemCurrency::whereIn('code', [$from, $to])->get()->keyBy('code');

        if (! isset($currencies[$from], $currencies[$to])) {
            return null;
        }

        $fromRate = (float) ($currencies[$from]->{$column} ?: $currencies[$from]->exchange_rate);
        $toRate = (float) ($currencies[$to]->{$column} ?: $currencies[$to]->exchange_rate);

        if ($fromRate <= 0 || $toRate <= 0) {
            return null;
        }

        $tryAmount = $amount * $fromRate;

        return round($tryAmount / $toRate, 4);
    }

    public function ratesForBar(bool $force = false): array
    {
        if ($force || $this->needsSync()) {
            try {
                $this->sync($force);
            } catch (\Throwable $e) {
                Log::warning('Kur çubuğu senkronu: ' . $e->getMessage());
            }
        }

        $codes = config('ticari.bar_currencies', ['USD', 'EUR', 'SAR']);

        return SystemCurrency::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($c) => $c->code === 'TRY' || in_array($c->code, $codes, true))
            ->map(fn ($c) => [
                'code' => $c->code,
                'name' => currency_name($c->code),
                'symbol' => $c->symbol,
                'rate' => (float) ($c->tcmb_rate ?: $c->exchange_rate),
                'tcmb_rate' => (float) ($c->tcmb_rate ?: $c->exchange_rate),
                'market_rate' => (float) ($c->market_rate ?: $c->exchange_rate),
                'updated' => $c->rate_updated_at,
            ])
            ->values()
            ->all();
    }

    public function barCurrencyCodes(): array
    {
        return config('ticari.bar_currencies', ['USD', 'EUR', 'SAR']);
    }
}
