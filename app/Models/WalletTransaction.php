<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'company_wallet_id',
        'type',
        'amount',
        'currency',
        'description',
        'counterparty',
        'receipt_no',
        'notes',
        'transaction_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CompanyWallet::class, 'company_wallet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return __('finance.wallet_types.' . $this->type);
    }

    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }
}
