<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeExpense extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'type',
        'category',
        'item_name',
        'vendor',
        'quantity',
        'unit',
        'unit_price',
        'payment_method',
        'receipt_no',
        'notes',
        'account_id',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'exchange_rate' => 'decimal:6',
            'amount_base' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function categoryLabel(): string
    {
        return finance_categories()->label($this->category);
    }

    public function displayTitle(): string
    {
        return $this->item_name ?: ($this->description ?: $this->categoryLabel());
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
