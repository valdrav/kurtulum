<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_treasury',
        'customer_id',
        'supplier_id',
        'currency',
        'opening_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'is_treasury' => 'boolean',
        ];
    }

    public function scopeTreasury($query)
    {
        return $query->where('is_treasury', true);
    }

    public function scopeCari($query)
    {
        return $query->where('is_treasury', false);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function incomeExpenses(): HasMany
    {
        return $this->hasMany(IncomeExpense::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->current_balance;
    }

    public function typeLabel(): string
    {
        return __('finance.account_types.' . $this->type) !== 'finance.account_types.' . $this->type
            ? __('finance.account_types.' . $this->type)
            : $this->type;
    }
}
