<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselPosition extends Model
{
    protected $fillable = [
        'vessel_id',
        'latitude',
        'longitude',
        'speed',
        'course',
        'source',
        'recorded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'speed' => 'decimal:2',
            'course' => 'decimal:2',
            'recorded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }
}
