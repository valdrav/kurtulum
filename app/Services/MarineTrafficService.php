<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Vessel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarineTrafficService
{
    protected string $baseUrl = 'https://services.marinetraffic.com/api';

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null && $this->apiKey() !== '';
    }

    public function apiKey(): ?string
    {
        $fromDb = Setting::get('marinetraffic_api_key');

        if ($fromDb) {
            return $fromDb;
        }

        return config('ticari.marinetraffic.api_key') ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchVessels(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        if (preg_match('/^\d{7}$/', $query)) {
            return $this->requestShipSearch(['imo' => $query]);
        }

        $digits = preg_replace('/\D/', '', $query);
        if (strlen($digits) === 7 && (stripos($query, 'imo') !== false || strlen($query) <= 10)) {
            return $this->requestShipSearch(['imo' => $digits]);
        }

        if (preg_match('/^\d{9}$/', $query)) {
            return $this->requestShipSearch(['mmsi' => $query]);
        }

        return $this->requestShipSearch(['shipname' => $query]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVesselPosition(Vessel $vessel): ?array
    {
        $params = array_filter([
            'imo' => $this->normalizeImo($vessel->imo_number),
            'mmsi' => $vessel->mmsi,
            'shipid' => $vessel->marinetraffic_ship_id,
        ]);

        if ($params === []) {
            return null;
        }

        $rows = $this->requestExportVessel($params);

        return $rows[0] ?? null;
    }

    public function upsertVessel(array $row): Vessel
    {
        $imo = $this->normalizeImo($row['IMO'] ?? $row['imo'] ?? null);
        $mmsi = $this->stringOrNull($row['MMSI'] ?? $row['mmsi'] ?? null);
        $shipId = $this->stringOrNull($row['SHIP_ID'] ?? $row['ship_id'] ?? null);
        $name = $row['SHIPNAME'] ?? $row['shipname'] ?? $row['SHIPNAME'] ?? 'Bilinmeyen Gemi';

        $attributes = [
            'name' => $name,
            'mmsi' => $mmsi,
            'flag_country' => $row['COUNTRY'] ?? $row['FLAG'] ?? null,
            'marinetraffic_ship_id' => $shipId,
            'vessel_type' => $row['TYPE_NAME'] ?? $row['SHIPTYPE'] ?? null,
            'callsign' => $row['CALLSIGN'] ?? null,
            'dwt' => $this->stringOrNull($row['DWT'] ?? null),
            'mt_url' => $row['MT_URL'] ?? $row['URL'] ?? null,
        ];

        if ($imo) {
            return Vessel::updateOrCreate(['imo_number' => $imo], $attributes);
        }

        if ($mmsi) {
            return Vessel::updateOrCreate(['mmsi' => $mmsi], $attributes);
        }

        if ($shipId) {
            return Vessel::updateOrCreate(['marinetraffic_ship_id' => $shipId], $attributes);
        }

        return Vessel::create(array_merge($attributes, ['imo_number' => null]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function requestShipSearch(array $params): array
    {
        $key = $this->apiKey();

        if (! $key) {
            return [];
        }

        try {
            $response = Http::timeout(25)
                ->withOptions(['allow_redirects' => true])
                ->get("{$this->baseUrl}/shipsearch/{$key}", array_merge($params, ['protocol' => 'jsono']));

            return $this->parseResponse($response->body());
        } catch (\Throwable $e) {
            Log::warning('MarineTraffic shipsearch: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function requestExportVessel(array $params): array
    {
        $key = $this->apiKey();

        if (! $key) {
            return [];
        }

        $query = array_merge($params, ['protocol' => 'jsono']);

        try {
            $response = Http::timeout(25)
                ->withOptions(['allow_redirects' => true])
                ->get("{$this->baseUrl}/exportvessel/{$key}", $query);

            $rows = $this->parseResponse($response->body());

            if ($rows !== []) {
                return $rows;
            }

            $imo = $params['imo'] ?? null;
            $mmsi = $params['mmsi'] ?? null;

            if ($imo) {
                $legacyUrl = "{$this->baseUrl}/exportvessel/v:8/{$key}/imo:{$imo}/protocol:jsono";
                $legacy = Http::timeout(25)->withOptions(['allow_redirects' => true])->get($legacyUrl);

                return $this->parseResponse($legacy->body());
            }

            if ($mmsi) {
                $legacyUrl = "{$this->baseUrl}/exportvessel/v:8/{$key}/mmsi:{$mmsi}/protocol:jsono";

                $legacy = Http::timeout(25)->withOptions(['allow_redirects' => true])->get($legacyUrl);

                return $this->parseResponse($legacy->body());
            }
        } catch (\Throwable $e) {
            Log::warning('MarineTraffic exportvessel: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseResponse(string $body): array
    {
        $body = trim($body);

        if ($body === '' || str_starts_with(strtoupper($body), 'ERROR') || str_contains(strtoupper($body), 'KEY NOT FOUND')) {
            Log::warning('MarineTraffic API yanıtı: ' . substr($body, 0, 200));

            return [];
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return [];
        }

        if (array_is_list($decoded)) {
            return array_values(array_filter($decoded, 'is_array'));
        }

        return [$decoded];
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
}
