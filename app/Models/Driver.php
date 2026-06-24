<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'license_expiry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function shipmentLegs(): HasMany
    {
        return $this->hasMany(ShipmentLeg::class);
    }
}
