<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vessel extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'imo_number',
        'mmsi',
        'flag_country',
        'marinetraffic_ship_id',
        'vessel_type',
        'callsign',
        'dwt',
        'gt',
        'length_m',
        'beam_m',
        'year_built',
        'mt_url',
        'tracked_at',
    ];

    protected function casts(): array
    {
        return [
            'tracked_at' => 'datetime',
        ];
    }

    public function identifierLabel(): string
    {
        $parts = array_filter([
            $this->imo_number ? 'IMO ' . $this->imo_number : null,
            $this->mmsi ? 'MMSI ' . $this->mmsi : null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    public function positions(): HasMany
    {
        return $this->hasMany(VesselPosition::class)->latest('recorded_at');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function shipmentLegs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class);
    }
}
