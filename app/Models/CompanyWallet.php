<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyWallet extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'holder_name',
        'bank_name',
        'iban',
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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('transaction_date')->latest('id');
    }

    public function displayLabel(): string
    {
        if ($this->holder_name) {
            return $this->name . ' · ' . $this->holder_name;
        }

        return $this->name;
    }
}
