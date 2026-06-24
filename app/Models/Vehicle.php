<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'plate_number',
        'type',
        'brand',
        'model',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shipmentLegs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class);
    }
}
