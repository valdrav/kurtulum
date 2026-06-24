<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentMilestone extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'name',
        'description',
        'status',
        'expected_at',
        'completed_at',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'expected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
