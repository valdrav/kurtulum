<?php

namespace App\Services;

use App\Models\Port;
use App\Models\Shipment;
use App\Models\Vessel;
use App\Models\VesselPosition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VesselTrackingService
{
    public function __construct(
        protected MarinesiaService $marinesia,
        protected MarineTrafficService $marineTraffic,
        protected VesselPublicInfoService $publicInfo,
        protected PortResolver $portResolver,
    ) {}

    public function isApiConfigured(): bool
    {
        return $this->marinesia->isConfigured() || $this->marineTraffic->isConfigured();
    }

    public function activeProvider(): ?string
    {
        if ($this->marinesia->isConfigured()) {
            return 'marinesia';
        }

        if ($this->marineTraffic->isConfigured()) {
            return 'marinetraffic';
        }

        return null;
    }

    public function search(string $query): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $vessels = collect();

        if ($this->marinesia->isConfigured()) {
            $live = $this->marinesia->lookup($query);

            if ($live) {
                $vessels->push($this->marinesia->upsertVessel($live));
            }
        }

        if ($vessels->isEmpty() && $this->marineTraffic->isConfigured()) {
            $rows = $this->marineTraffic->searchVessels($query);

            foreach ($rows as $row) {
                if (empty($row['SHIPNAME'] ?? $row['shipname'] ?? null)) {
                    continue;
                }

                $vessels->push($this->marineTraffic->upsertVessel($row));
            }
        }

        if ($vessels->isEmpty()) {
            $local = Vessel::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('imo_number', 'like', "%{$query}%")
                        ->orWhere('mmsi', 'like', "%{$query}%");
                })
                ->limit(20)
                ->get();

            $vessels = $local;
        }

        return $vessels->unique(fn (Vessel $v) => $v->imo_number ?: ('mmsi:' . ($v->mmsi ?? $v->id)))->values();
    }

    public function track(Vessel $vessel, bool $forceRefresh = false): array
    {
        $liveMeta = null;
        $position = null;
        $source = 'none';
        $fromCache = false;
        $refreshMinutes = max(5, (int) config('ticari.vessel_tracking.refresh_minutes', 15));
        $lastPosition = $vessel->positions()->latest('recorded_at')->first();
        $shouldFetchLive = $forceRefresh
            || ! $lastPosition
            || ! in_array($lastPosition->source, ['marinesia', 'marinetraffic'], true)
            || $lastPosition->recorded_at->lt(now()->subMinutes($refreshMinutes));

        if ($shouldFetchLive) {
            $vessel->positions()->where('source', 'public_estimate')->delete();
        }

        if ($shouldFetchLive && $this->marinesia->isConfigured()) {
            $liveMeta = $this->marinesia->getLatestLocation($vessel->imo_number, $vessel->mmsi, $forceRefresh);

            if (! $liveMeta && $vessel->mmsi) {
                $liveMeta = $this->marinesia->getLatestLocation(null, $vessel->mmsi, $forceRefresh);
            }

            if ($liveMeta) {
                $vessel = $this->marinesia->upsertVessel($liveMeta, $vessel->name);
                $position = $this->storeAisPosition($vessel, $liveMeta, 'marinesia');
                $source = 'marinesia';
            }
        }

        if (! $position && $shouldFetchLive && $this->marineTraffic->isConfigured()) {
            $liveMeta = $this->marineTraffic->getVesselPosition($vessel);

            if ($liveMeta) {
                $position = $this->storeAisPosition($vessel, $this->normalizeMarineTrafficRow($liveMeta), 'marinetraffic');
                $source = 'marinetraffic';
                $vessel = $this->marineTraffic->upsertVessel($liveMeta);
            }
        }

        if (! $position && $lastPosition && in_array($lastPosition->source, ['marinesia', 'marinetraffic'], true)) {
            $position = $lastPosition;
            $source = $lastPosition->source;
            $fromCache = true;
            $liveMeta = is_array($lastPosition->meta) ? $lastPosition->meta : null;
        }

        if (! $position) {
            $position = $this->estimateFromActiveShipment($vessel);
            if ($position) {
                $source = $position->source;
            }
        }

        if (! $liveMeta && $vessel->imo_number) {
            $liveMeta = $this->publicInfo->fetchPublicAisSnapshot($vessel->imo_number, $vessel->mmsi);
            if ($liveMeta && $source === 'none') {
                $source = 'vesselfinder';
            }
        }

        if ($vessel->imo_number) {
            $vessel = $this->publicInfo->syncVessel($vessel->fresh());
            if (is_array($liveMeta)) {
                $liveMeta = array_merge($liveMeta, array_filter([
                    'mmsi' => $vessel->mmsi,
                    'name' => $vessel->name,
                ]));
            }
        } elseif (! empty($liveMeta['mmsi']) && ! $vessel->mmsi) {
            $vessel->update(['mmsi' => $liveMeta['mmsi']]);
        }

        if (! $position && is_array($liveMeta) && ($liveMeta['source'] ?? '') === 'vesselfinder') {
            $position = $this->buildPublicAisEstimate($vessel, $liveMeta);
            if ($position) {
                $source = 'public_estimate';
            }
        }

        $history = $vessel->positions()->latest('recorded_at')->limit(30)->get();
        $activeShipment = $this->activeShipment($vessel);
        $voyagePlan = $this->buildVoyagePlan($vessel, $liveMeta ?? $position?->meta, $activeShipment);

        return [
            'vessel' => $vessel->fresh(),
            'position' => $position,
            'history' => $history,
            'source' => $source,
            'liveMeta' => $liveMeta ?? $position?->meta,
            'embedImo' => $vessel->imo_number,
            'voyagePlan' => $voyagePlan,
            'activeShipment' => $activeShipment,
            'fromCache' => $fromCache,
            'positionStale' => $position && $position->exists && $position->recorded_at->lt(now()->subMinutes($refreshMinutes)),
            'positionEstimated' => in_array($source, ['public_estimate', 'estimated'], true),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function storeAisPosition(Vessel $vessel, array $data, string $source): ?VesselPosition
    {
        $lat = $data['lat'] ?? $data['LAT'] ?? null;
        $lon = $data['lng'] ?? $data['lon'] ?? $data['LON'] ?? $data['LNG'] ?? null;

        if ($lat === null || $lon === null || $lat === '' || $lon === '') {
            return null;
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        if (! $this->isValidCoordinate($lat, $lon)) {
            return null;
        }

        $recent = $vessel->positions()
            ->where('source', $source)
            ->where('recorded_at', '>=', now()->subMinutes(30))
            ->latest('recorded_at')
            ->first();

        if ($recent
            && abs($recent->latitude - $lat) < 0.0001
            && abs($recent->longitude - $lon) < 0.0001) {
            return $recent;
        }

        $recordedAt = now();
        $timestamp = $data['ts'] ?? $data['TIMESTAMP'] ?? null;

        if ($timestamp) {
            try {
                $recordedAt = \Illuminate\Support\Carbon::parse($timestamp);
            } catch (\Throwable) {
                // keep now()
            }
        }

        $status = $data['status'] ?? $data['STATUS'] ?? null;

        if (is_numeric($status)) {
            $status = $this->navStatusLabel((int) $status);
        }

        return VesselPosition::create([
            'vessel_id' => $vessel->id,
            'latitude' => (float) $lat,
            'longitude' => (float) $lon,
            'speed' => isset($data['sog']) && $data['sog'] !== '' ? (float) $data['sog'] : (isset($data['SPEED']) ? (float) $data['SPEED'] : null),
            'course' => isset($data['cog']) && $data['cog'] !== '' ? (float) $data['cog'] : (isset($data['COURSE']) ? (float) $data['COURSE'] : (isset($data['hdt']) ? (float) $data['hdt'] : null)),
            'source' => $source,
            'recorded_at' => $recordedAt,
            'meta' => [
                'destination' => $data['dest'] ?? $data['DESTINATION'] ?? null,
                'eta' => $data['eta'] ?? $data['ETA'] ?? null,
                'status' => $status,
                'draught' => $data['draught'] ?? $data['DRAUGHT'] ?? null,
                'imo' => $data['imo'] ?? $data['IMO'] ?? null,
                'mmsi' => $data['mmsi'] ?? $data['MMSI'] ?? null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeMarineTrafficRow(array $row): array
    {
        return [
            'lat' => $row['LAT'] ?? $row['lat'] ?? null,
            'lng' => $row['LON'] ?? $row['lon'] ?? $row['LNG'] ?? null,
            'sog' => $row['SPEED'] ?? null,
            'cog' => $row['COURSE'] ?? $row['HEADING'] ?? null,
            'ts' => $row['TIMESTAMP'] ?? null,
            'dest' => $row['DESTINATION'] ?? null,
            'eta' => $row['ETA'] ?? null,
            'status' => $row['STATUS'] ?? null,
            'draught' => $row['DRAUGHT'] ?? null,
        ];
    }

    protected function navStatusLabel(int $code): string
    {
        return match ($code) {
            0 => 'Motorla seyir',
            1 => 'Demirde',
            2 => 'Kontrol dışı',
            3 => 'Manevra kabiliyeti kısıtlı',
            4 => 'Sınırlı manevra',
            5 => 'Bağlı',
            6 => 'Karaya oturmuş',
            7 => 'Balıkçılık',
            8 => 'Yelkenle seyir',
            default => 'Durum: ' . $code,
        };
    }

    protected function isValidCoordinate(float $lat, float $lng): bool
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        return ! ($lat == 0.0 && $lng == 0.0);
    }

    protected function estimateFromActiveShipment(Vessel $vessel): ?VesselPosition
    {
        $shipment = Shipment::query()
            ->where('vessel_id', $vessel->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->with(['originPort', 'destinationPort'])
            ->latest()
            ->first();

        if (! $shipment?->originPort || ! $shipment?->destinationPort) {
            return null;
        }

        $origin = $this->portCoords($shipment->originPort);
        $dest = $this->portCoords($shipment->destinationPort);

        if (! $origin || ! $dest) {
            return null;
        }

        $progress = match ($shipment->status) {
            'draft', 'booked' => 0.05,
            'planned', 'loading' => 0.12,
            'port_waiting', 'awaiting_transit', 'at_port' => 0.18,
            'in_transit' => 0.55,
            'discharging', 'customs' => 0.85,
            default => 0.35,
        };

        if ($shipment->etd && $shipment->eta) {
            $total = max(1, $shipment->etd->diffInSeconds($shipment->eta));
            $elapsed = max(0, $shipment->etd->diffInSeconds(now()));
            $progress = max($progress, min(0.95, $elapsed / $total));
        }

        $lat = $origin['lat'] + ($dest['lat'] - $origin['lat']) * $progress;
        $lng = $origin['lng'] + ($dest['lng'] - $origin['lng']) * $progress;

        return VesselPosition::create([
            'vessel_id' => $vessel->id,
            'latitude' => round($lat, 6),
            'longitude' => round($lng, 6),
            'speed' => 12,
            'course' => null,
            'source' => 'estimated',
            'recorded_at' => now(),
            'meta' => ['note' => 'Aktif sevkiyat rotasından tahmin'],
        ]);
    }

    /**
     * VesselFinder sefer verisinden geçici tahmini konum (veritabanına yazılmaz).
     *
     * @param  array<string, mixed>  $snapshot
     */
    protected function buildPublicAisEstimate(Vessel $vessel, array $snapshot): ?VesselPosition
    {
        $origin = $this->resolvePortCoordsByName($snapshot['last_port'] ?? null);
        $dest = $this->resolvePortCoordsByName($snapshot['dest'] ?? null);

        if ($origin && $dest) {
            $progress = $this->voyageProgressFromSnapshot($snapshot);
            $lat = $origin['lat'] + ($dest['lat'] - $origin['lat']) * $progress;
            $lng = $origin['lng'] + ($dest['lng'] - $origin['lng']) * $progress;
        } elseif (isset($snapshot['ship_lat'], $snapshot['ship_lon'])) {
            $lat = (float) $snapshot['ship_lat'];
            $lng = (float) $snapshot['ship_lon'];
            if (! $this->isValidCoordinate($lat, $lng)) {
                return null;
            }
        } else {
            return null;
        }

        return new VesselPosition([
            'vessel_id' => $vessel->id,
            'latitude' => round($lat, 6),
            'longitude' => round($lng, 6),
            'speed' => isset($snapshot['sog']) ? (float) $snapshot['sog'] : null,
            'course' => isset($snapshot['cog']) ? (float) $snapshot['cog'] : null,
            'source' => 'public_estimate',
            'recorded_at' => now(),
            'meta' => [
                'note' => 'Sefer planından tahmini konum (canlı AIS yok)',
                'destination' => $snapshot['dest'] ?? null,
                'eta' => $snapshot['eta'] ?? null,
                'status' => $snapshot['status'] ?? null,
                'last_port' => $snapshot['last_port'] ?? null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function voyageProgressFromSnapshot(array $snapshot): float
    {
        $atd = $this->parsePublicDate($snapshot['atd_raw'] ?? null);
        $eta = $this->parsePublicDate($snapshot['eta'] ?? null);

        if ($atd && $eta && $eta->gt($atd)) {
            $total = max(1, $atd->diffInSeconds($eta));
            $elapsed = max(0, min($total, $atd->diffInSeconds(now())));

            return max(0.02, min(0.98, $elapsed / $total));
        }

        return 0.12;
    }

    protected function parsePublicDate(?string $value): ?\Illuminate\Support\Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', $value));

        try {
            $parsed = \Illuminate\Support\Carbon::parse($value);

            if ($parsed->isFuture() && $parsed->diffInDays(now()) > 30) {
                $parsed->subYear();
            }

            return $parsed;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function estimateFromPublicAis(Vessel $vessel, array $snapshot): ?VesselPosition
    {
        return $this->buildPublicAisEstimate($vessel, $snapshot);
    }

    protected function resolvePortCoordsByName(?string $name): ?array
    {
        if (! $name) {
            return null;
        }

        $name = trim($name);
        $slug = strtoupper(preg_replace('/[^A-Za-z]/', '', substr($name, 0, 5)));

        $known = config('ticari.port_coordinates.' . $slug);
        if ($known) {
            return $known;
        }

        $port = Port::query()
            ->where('name', 'like', '%' . explode(',', $name)[0] . '%')
            ->whereNotNull('latitude')
            ->first();

        if ($port) {
            return ['lat' => (float) $port->latitude, 'lng' => (float) $port->longitude];
        }

        foreach (config('ticari.port_coordinates', []) as $code => $coords) {
            if (stripos($name, strtolower(substr($code, 2))) !== false) {
                return $coords;
            }
        }

        $aliases = [
            'nemrut' => config('ticari.port_coordinates.TRNEM'),
            'misurata' => config('ticari.port_coordinates.LYMIS'),
            'aliaga' => config('ticari.port_coordinates.TRNEM'),
        ];

        foreach ($aliases as $needle => $coords) {
            if (stripos($name, $needle) !== false && $coords) {
                return $coords;
            }
        }

        return null;
    }

    protected function portCoords(Port $port): ?array
    {
        if ($port->latitude && $port->longitude) {
            return ['lat' => (float) $port->latitude, 'lng' => (float) $port->longitude];
        }

        $known = config('ticari.port_coordinates.' . strtoupper($port->code));

        return $known ?: null;
    }

    protected function activeShipment(Vessel $vessel): ?Shipment
    {
        return Shipment::query()
            ->where('vessel_id', $vessel->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->with(['originPort', 'destinationPort', 'order'])
            ->latest()
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $aisMeta
     * @return array<string, mixed>
     */
    protected function buildVoyagePlan(Vessel $vessel, ?array $aisMeta, ?Shipment $shipment): array
    {
        $meta = is_array($aisMeta) ? $aisMeta : [];
        $aisDest = trim((string) ($meta['destination'] ?? $meta['DESTINATION'] ?? $meta['dest'] ?? ''));
        $aisEta = $this->parseAisEta($meta['eta'] ?? $meta['ETA'] ?? null);
        $aisStatus = $meta['status'] ?? $meta['STATUS'] ?? null;
        $draught = $meta['draught'] ?? $meta['DRAUGHT'] ?? null;

        $originLabel = $shipment?->originPort
            ? $this->portResolver->label($shipment->originPort)
            : port_display_label($shipment?->origin);
        $destinationLabel = $shipment?->destinationPort
            ? $this->portResolver->label($shipment->destinationPort)
            : port_display_label($shipment?->destination);
        $aisDestLabel = $aisDest !== '' ? $this->portResolver->label(null, $aisDest) : null;
        $nextPortLabel = $aisDestLabel ?? $destinationLabel;

        $plannedEta = $shipment?->eta;
        $plannedEtd = $shipment?->etd;

        return [
            'nav_status' => $aisStatus,
            'ais_destination' => $aisDestLabel,
            'ais_destination_raw' => $aisDest !== '' ? $aisDest : null,
            'ais_eta' => $aisEta,
            'ais_eta_label' => $aisEta?->timezone(app_timezone())->format('d.m.Y H:i'),
            'draught' => $draught,
            'next_port' => $nextPortLabel,
            'origin_port' => $originLabel,
            'destination_port' => $destinationLabel,
            'planned_etd' => $plannedEtd,
            'planned_etd_label' => $plannedEtd?->timezone(app_timezone())->format('d.m.Y H:i'),
            'planned_eta' => $plannedEta,
            'planned_eta_label' => $plannedEta?->timezone(app_timezone())->format('d.m.Y H:i'),
            'shipment_status' => $shipment?->status,
            'shipment_number' => $shipment?->shipment_number,
            'bl_number' => $shipment?->bl_number,
            'voyage_number' => $shipment?->voyage_number,
        ];
    }

    protected function parseAisEta(mixed $eta): ?\Illuminate\Support\Carbon
    {
        if ($eta === null || $eta === '') {
            return null;
        }

        if ($eta instanceof \DateTimeInterface) {
            return \Illuminate\Support\Carbon::instance($eta);
        }

        $eta = trim((string) $eta);

        try {
            if (preg_match('/^(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $eta, $m)) {
                $year = (int) now()->format('Y');
                $candidate = \Illuminate\Support\Carbon::create($year, (int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4]);
                if ($candidate->isPast()) {
                    $candidate->addYear();
                }

                return $candidate;
            }

            return \Illuminate\Support\Carbon::parse($eta);
        } catch (\Throwable) {
            return null;
        }
    }
}
