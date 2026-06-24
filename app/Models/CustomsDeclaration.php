<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomsDeclaration extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'declaration_number',
        'declaration_type',
        'status',
        'declared_at',
        'cleared_at',
        'customs_office',
        'total_value',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'declared_at' => 'date',
            'cleared_at' => 'date',
            'total_value' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
