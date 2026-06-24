<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Container extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'container_number',
        'type',
        'seal_number',
        'tare_weight',
        'max_payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tare_weight' => 'decimal:2',
            'max_payload' => 'decimal:2',
        ];
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'shipment_container');
    }
}
