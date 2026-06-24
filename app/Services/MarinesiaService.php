<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Vessel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarinesiaService
{
    protected string $baseUrl = 'https://api.marinesia.com/api/v2';

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null && $this->apiKey() !== '';
    }

    public function apiKey(): ?string
    {
        $fromDb = Setting::get('marinesia_api_key');

        if ($fromDb) {
            return $fromDb;
        }

        return config('ticari.vessel_tracking.marinesia.api_key') ?: null;
    }

    /**
     * IMO veya MMSI ile gemi konumu sorgula (önbellekli).
     *
     * @return array<string, mixed>|null
     */
    public function getLatestLocation(?string $imo = null, ?string $mmsi = null, bool $force = false): ?array
    {
        $imo = $this->normalizeImo($imo);
        $mmsi = $this->stringOrNull($mmsi);

        if (! $imo && ! $mmsi) {
            return null;
        }

        $cacheKey = $this->cacheKey($imo, $mmsi);
        $ttl = max(5, (int) config('ticari.vessel_tracking.cache_minutes', 20));

        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $fresh = $this->fetchLatestLocation($imo, $mmsi);

        if ($fresh) {
            Cache::put($cacheKey, $fresh, now()->addMinutes($ttl));
            Cache::put($cacheKey . ':backup', $fresh, now()->addDay());

            return $fresh;
        }

        return Cache::get($cacheKey . ':backup');
    }

    /**
     * Arama metninden (IMO/MMSI) konum çek.
     *
     * @return array<string, mixed>|null
     */
    public function lookup(string $query): ?array
    {
        $query = trim($query);

        if ($query === '' || ! $this->isConfigured()) {
            return null;
        }

        if (preg_match('/^\d{7}$/', $query)) {
            return $this->getLatestLocation($query, null);
        }

        $digits = preg_replace('/\D/', '', $query);

        if (strlen($digits) === 7 && (stripos($query, 'imo') !== false || strlen($query) <= 10)) {
            return $this->getLatestLocation($digits, null);
        }

        if (preg_match('/^\d{9}$/', $query)) {
            return $this->getLatestLocation(null, $query);
        }

        if (strlen($digits) === 9) {
            return $this->getLatestLocation(null, $digits);
        }

        return null;
    }

    public function upsertVessel(array $data, ?string $fallbackName = null): Vessel
    {
        $imo = $this->normalizeImo($data['imo'] ?? null);
        $mmsi = $this->stringOrNull($data['mmsi'] ?? null);
        $name = $data['name'] ?? $data['shipname'] ?? $data['SHIPNAME'] ?? null;
        $name = $name ?: ($fallbackName ?? ($imo ? "Gemi {$imo}" : ($mmsi ? "Gemi {$mmsi}" : 'Bilinmeyen Gemi')));

        $existing = null;

        if ($imo) {
            $existing = Vessel::where('imo_number', $imo)->first();
        }

        if (! $existing && $mmsi) {
            $existing = Vessel::where('mmsi', $mmsi)->first();
        }

        if ($existing && $existing->name && ! str_starts_with($existing->name, 'Gemi ')) {
            $name = $existing->name;
        }

        $attributes = [
            'name' => $name,
            'mmsi' => $mmsi,
            'imo_number' => $imo,
            'tracked_at' => now(),
        ];

        if ($imo) {
            return Vessel::updateOrCreate(['imo_number' => $imo], $attributes);
        }

        if ($mmsi) {
            return Vessel::updateOrCreate(['mmsi' => $mmsi], $attributes);
        }

        return Vessel::create(array_merge($attributes, ['imo_number' => null]));
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchLatestLocation(?string $imo, ?string $mmsi): ?array
    {
        $params = array_filter([
            'imo' => $imo,
            'mmsi' => $mmsi,
            'key' => $this->apiKey(),
        ]);

        return $this->request('/vessel/location/latest', $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function request(string $path, array $params): ?array
    {
        if (empty($params['key'])) {
            return null;
        }

        try {
            $response = $this->client()->get($this->baseUrl . $path, $params);
            $body = $response->json();

            if ($response->status() === 429) {
                Log::warning('Marinesia API: istek limiti (429) — önbellek kullanılacak');

                return null;
            }

            if (! is_array($body)) {
                Log::warning('Marinesia API geçersiz yanıt: ' . substr($response->body(), 0, 200));

                return null;
            }

            if (($body['error'] ?? true) !== false) {
                Log::warning('Marinesia API: ' . ($body['message'] ?? $response->body()));

                return null;
            }

            $data = $body['data'] ?? null;

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            Log::warning('Marinesia API hatası: ' . $e->getMessage());

            return null;
        }
    }

    protected function cacheKey(?string $imo, ?string $mmsi): string
    {
        return 'marinesia:loc:' . ($imo ?: '0') . ':' . ($mmsi ?: '0');
    }

    protected function normalizeImo(mixed $imo): ?string
    {
        if ($imo === null || $imo === '' || $imo === '0' || $imo === 0) {
            return null;
        }

        return preg_replace('/\D/', '', (string) $imo) ?: null;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (string) $value;
    }

    protected function client()
    {
        return Http::timeout(20)
            ->withHeaders(['User-Agent' => 'KurtulumERP/1.0'])
            ->when(! http_verify_ssl(), fn ($r) => $r->withOptions(['verify' => false]));
    }
}
