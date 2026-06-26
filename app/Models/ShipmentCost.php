<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShipmentCost extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipment_id',
        'type',
        'item_name',
        'description',
        'payee',
        'country',
        'amount',
        'amount_try',
        'exchange_rate',
        'currency',
        'supplier_id',
        'invoice_number',
        'expense_date',
        'notes',
        'status',
        'paid_at',
        'treasury_posted_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_try' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'expense_date' => 'date',
            'paid_at' => 'date',
            'treasury_posted_at' => 'datetime',
        ];
    }

    public function displayTitle(): string
    {
        return $this->item_name ?: ($this->description ?: '—');
    }

    public function statusLabel(): string
    {
        return __('logistics.cost_status.' . ($this->status ?: 'pending'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
