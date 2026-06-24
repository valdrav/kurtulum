<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterOfCredit extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'order_id',
        'customer_id',
        'shipment_id',
        'lc_number',
        'issuing_bank',
        'advising_bank',
        'amount',
        'currency',
        'issue_date',
        'expiry_date',
        'status',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
