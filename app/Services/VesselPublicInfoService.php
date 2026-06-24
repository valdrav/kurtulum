<?php

namespace App\Services;

use App\Models\Vessel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VesselPublicInfoService
{
    /**
     * VesselFinder sayfasından gemi profili ve AIS özeti.
     *
     * @return array<string, mixed>|null
     */
    public function fetchVesselProfile(?string $imo, ?string $mmsi = null): ?array
    {
        $imo = preg_replace('/\D/', '', (string) $imo);

        if (strlen($imo) !== 7) {
            return null;
        }

        try {
            $response = $this->client()->get("https://www.vesselfinder.com/vessels/details/{$imo}");

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            $text = html_entity_decode(strip_tags(str_replace(['><', '<br>', '<br/>', '<br />'], ['> <', "\n", "\n", "\n"], $html)));
            $text = preg_replace('/\s+/', ' ', $text);

            $profile = [
                'source' => 'vesselfinder',
                'imo' => $imo,
                'mmsi' => $mmsi,
            ];

            if (preg_match('/<title>([^<]+)</i', $html, $match)) {
                $title = html_entity_decode(trim($match[1]));
                $name = explode(',', $title)[0] ?? null;
                if ($name && strlen($name) > 1 && ! str_contains(strtolower($name), 'vesselfinder')) {
                    $profile['name'] = trim($name);
                }
            }

            if (preg_match('/IMO\s+(\d{7})/i', $text, $m)) {
                $profile['imo'] = $m[1];
            }

            if (preg_match('/MMSI\s+(\d{9})/i', $text, $m)) {
                $profile['mmsi'] = $m[1];
            }

            if (preg_match('/Call\s*Sign\s+([A-Z0-9\-]+)/i', $text, $m)) {
                $profile['callsign'] = trim($m[1]);
            }

            if (preg_match('/Flag\s+([A-Za-z][A-Za-z\s]+?)(?:\s+AIS|\s+IMO|\s+MMSI|\s+Call)/i', $text, $m)) {
                $profile['flag_country'] = trim($m[1]);
            }

            if (preg_match('/AIS\s+Type\s+([^\.]+?)(?:\s+Gross|\s+Deadweight|\s+Length|\s+Year|\s+IMO)/i', $text, $m)) {
                $profile['vessel_type'] = trim($m[1]);
            } elseif (preg_match('/Ship\s+Type\s+([^\.]+?)(?:\s+Gross|\s+Deadweight|\s+Length|\s+Year)/i', $text, $m)) {
                $profile['vessel_type'] = trim($m[1]);
            }

            if (preg_match('/Gross\s+Tonnage\s+([\d,\.]+)/i', $text, $m)) {
                $profile['gt'] = str_replace(',', '', $m[1]);
            }

            if (preg_match('/Deadweight\s+([\d,\.]+)/i', $text, $m)) {
                $profile['dwt'] = str_replace(',', '', $m[1]);
            }

            if (preg_match('/Length\s+Overall\s+([\d,\.]+)\s*m/i', $text, $m)) {
                $profile['length_m'] = $m[1];
            }

            if (preg_match('/Beam\s+([\d,\.]+)\s*m/i', $text, $m)) {
                $profile['beam_m'] = $m[1];
            }

            if (preg_match('/Year\s+of\s+Build\s+(\d{4})/i', $text, $m)) {
                $profile['year_built'] = (int) $m[1];
            }

            if (preg_match('/en route to the port of ([^,]+(?:,\s*[^,]+)?), sailing at a speed of ([\d.]+) knots/i', $text, $m)) {
                $profile['dest'] = trim($m[1]);
                $profile['sog'] = (float) $m[2];
            }

            if (preg_match('/expected to arrive there on ([^\.]+)/i', $text, $m)) {
                $profile['eta'] = trim($m[1]);
            }

            if (preg_match('/Navigation Status\s+([A-Za-z][A-Za-z\s]+?)(?:\s+Position|\s+IMO)/i', $text, $m)) {
                $profile['status'] = trim($m[1]);
            }

            if (preg_match('/Destination\s+([^\s].+?)\s+ETA:/i', $text, $m)) {
                $profile['dest'] = trim($m[1]);
            }

            if (preg_match('/ETA:\s*([^|]+?)(?:\s*\(|$)/i', $text, $m)) {
                $profile['eta'] = trim($m[1]);
            }

            if (preg_match('/Current draught\s+([\d.]+)\s*m/i', $text, $m)) {
                $profile['draught'] = (float) $m[1];
            }

            if (preg_match('/Course \/ Speed\s+([\d.]+)\s*\/\s*([\d.]+)/i', $html, $m)) {
                $profile['cog'] = (float) $m[1];
                $profile['sog'] = (float) $m[2];
            }

            if (preg_match('/Last Port.*?<a[^>]+>([^<]+)<\/a>/is', $html, $m)) {
                $profile['last_port'] = trim(html_entity_decode(strip_tags($m[1])));
            } elseif (preg_match('/Last Port\s+([^<\n]+?)\s+ATD:/i', $html, $m)) {
                $profile['last_port'] = trim(html_entity_decode(strip_tags($m[1])));
            }

            if (preg_match('/ATD:\s*([^<]+?UTC)/i', $html, $m)) {
                $profile['atd_raw'] = trim(strip_tags($m[1]));
            }

            if (preg_match("/data-json='([^']+)'/", $html, $m)) {
                $djson = json_decode(html_entity_decode($m[1]), true);
                if (is_array($djson)) {
                    if (isset($djson['ship_lat'], $djson['ship_lon'])) {
                        $profile['ship_lat'] = (float) $djson['ship_lat'];
                        $profile['ship_lon'] = (float) $djson['ship_lon'];
                    }
                    if (isset($djson['ship_cog'])) {
                        $profile['cog'] = (float) $djson['ship_cog'];
                    }
                    if (isset($djson['ship_sog'])) {
                        $profile['sog'] = (float) $djson['ship_sog'];
                    }
                }
            }

            return count($profile) > 3 ? $profile : null;
        } catch (\Throwable $e) {
            Log::warning('VesselFinder profili alınamadı: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * IMO ile gemi adını açık web kaynağından çöz (API anahtarı gerekmez).
     */
    public function resolveNameByImo(string $imo): ?string
    {
        return $this->fetchVesselProfile($imo)['name'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchPublicAisSnapshot(?string $imo, ?string $mmsi = null): ?array
    {
        return $this->fetchVesselProfile($imo, $mmsi);
    }

    public function syncVessel(Vessel $vessel, bool $markTracked = false): Vessel
    {
        if (! $vessel->imo_number) {
            return $vessel;
        }

        $profile = $this->fetchVesselProfile($vessel->imo_number, $vessel->mmsi);

        if (! $profile) {
            return $vessel;
        }

        return $this->applyProfile($vessel, $profile, $markTracked);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    public function applyProfile(Vessel $vessel, array $profile, bool $markTracked = false): Vessel
    {
        $imo = preg_replace('/\D/', '', (string) ($profile['imo'] ?? $vessel->imo_number ?? ''));
        $mmsi = $this->normalizeMmsi($profile['mmsi'] ?? $vessel->mmsi);

        if (strlen($imo) === 7) {
            $duplicate = Vessel::query()
                ->where('imo_number', $imo)
                ->where('id', '!=', $vessel->id)
                ->first();

            if ($duplicate) {
                $vessel = $this->mergeVessels($duplicate, $vessel);
            }
        }

        if ($mmsi) {
            $duplicate = Vessel::query()
                ->where('mmsi', $mmsi)
                ->where('id', '!=', $vessel->id)
                ->first();

            if ($duplicate) {
                $vessel = $this->mergeVessels($vessel, $duplicate);
            }
        }

        $updates = array_filter([
            'name' => $profile['name'] ?? null,
            'imo_number' => strlen($imo) === 7 ? $imo : $vessel->imo_number,
            'mmsi' => $mmsi,
            'callsign' => $profile['callsign'] ?? null,
            'flag_country' => $profile['flag_country'] ?? null,
            'vessel_type' => $profile['vessel_type'] ?? null,
            'dwt' => isset($profile['dwt']) ? (string) $profile['dwt'] : null,
            'gt' => isset($profile['gt']) ? (string) $profile['gt'] : null,
            'length_m' => isset($profile['length_m']) ? (string) $profile['length_m'] : null,
            'beam_m' => isset($profile['beam_m']) ? (string) $profile['beam_m'] : null,
            'year_built' => $profile['year_built'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($markTracked) {
            $updates['tracked_at'] = now();
        } elseif (! $vessel->tracked_at) {
            $updates['tracked_at'] = now();
        }

        if ($updates !== []) {
            if (isset($updates['name']) && str_starts_with($vessel->name, 'Gemi ') && ! str_starts_with($updates['name'], 'Gemi ')) {
                // keep resolved name
            } elseif (! isset($updates['name']) || str_starts_with($updates['name'], 'Gemi ')) {
                unset($updates['name']);
            }

            $vessel->update($updates);
        }

        return $vessel->fresh();
    }

    public function findOrCreateByIdentifier(string $query): ?Vessel
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        [$imo, $mmsi] = $this->parseIdentifier($query);

        if (! $imo && ! $mmsi) {
            return null;
        }

        $vessel = null;

        if ($imo) {
            $vessel = Vessel::where('imo_number', $imo)->first();
        }

        if (! $vessel && $mmsi) {
            $vessel = Vessel::where('mmsi', $mmsi)->first();
        }

        if (! $vessel) {
            $vessel = Vessel::create([
                'name' => $imo ? "Gemi {$imo}" : "Gemi {$mmsi}",
                'imo_number' => $imo,
                'mmsi' => $mmsi,
                'tracked_at' => now(),
            ]);
        } else {
            $vessel->update(['tracked_at' => now()]);
        }

        if ($vessel->imo_number) {
            return $this->syncVessel($vessel, true);
        }

        return $vessel->fresh();
    }

    protected function mergeVessels(Vessel $primary, Vessel $secondary): Vessel
    {
        if ($primary->id === $secondary->id) {
            return $primary;
        }

        $fill = array_filter([
            'mmsi' => $primary->mmsi ?: $secondary->mmsi,
            'callsign' => $primary->callsign ?: $secondary->callsign,
            'flag_country' => $primary->flag_country ?: $secondary->flag_country,
            'vessel_type' => $primary->vessel_type ?: $secondary->vessel_type,
            'dwt' => $primary->dwt ?: $secondary->dwt,
            'gt' => $primary->gt ?: $secondary->gt,
            'length_m' => $primary->length_m ?: $secondary->length_m,
            'beam_m' => $primary->beam_m ?: $secondary->beam_m,
            'year_built' => $primary->year_built ?: $secondary->year_built,
            'tracked_at' => $primary->tracked_at ?? $secondary->tracked_at ?? now(),
        ], fn ($v) => $v !== null && $v !== '');

        if (str_starts_with($primary->name, 'Gemi ') && ! str_starts_with($secondary->name, 'Gemi ')) {
            $fill['name'] = $secondary->name;
        }

        if ($fill !== []) {
            $primary->update($fill);
        }

        if (! $secondary->shipments()->exists()) {
            $secondary->delete();
        }

        return $primary->fresh();
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function parseIdentifier(string $query): array
    {
        $imo = null;
        $mmsi = null;

        if (preg_match('/^\d{7}$/', $query)) {
            $imo = $query;
        } elseif (preg_match('/^\d{9}$/', $query)) {
            $mmsi = $query;
        } else {
            $digits = preg_replace('/\D/', '', $query);

            if (strlen($digits) === 7 && (stripos($query, 'imo') !== false || strlen($query) <= 10)) {
                $imo = $digits;
            } elseif (strlen($digits) === 9) {
                $mmsi = $digits;
            }
        }

        return [$imo, $mmsi];
    }

    protected function normalizeMmsi(mixed $mmsi): ?string
    {
        $mmsi = preg_replace('/\D/', '', (string) $mmsi);

        return strlen($mmsi) === 9 ? $mmsi : null;
    }

    protected function client()
    {
        return Http::timeout(20)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; KurtulumERP/1.0)'])
            ->when(! http_verify_ssl(), fn ($r) => $r->withOptions(['verify' => false]));
    }
}
