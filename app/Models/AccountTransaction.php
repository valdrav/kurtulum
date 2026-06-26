<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'currency',
        'exchange_rate',
        'reference_type',
        'reference_id',
        'description',
        'transaction_date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'transaction_date' => 'date',
        ];
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

    public function counterpartyLabel(): ?string
    {
        $reference = $this->relationLoaded('reference') ? $this->reference : $this->reference()->first();

        if (! $reference) {
            return null;
        }

        if ($reference instanceof Collection) {
            return $reference->customer?->company_name
                ?? $reference->account?->customer?->company_name
                ?? $reference->account?->name;
        }

        if ($reference instanceof Payment) {
            return $reference->supplier?->company_name
                ?? $reference->account?->supplier?->company_name
                ?? $reference->account?->name;
        }

        if ($reference instanceof Order) {
            return $reference->customer?->company_name
                ?? $reference->supplier?->company_name;
        }

        if ($reference instanceof IncomeExpense) {
            return $reference->vendor ?: $reference->item_name;
        }

        return null;
    }

    public function typeLabelTr(): string
    {
        return match ($this->type) {
            'credit' => __('finance.tx_credit'),
            'debit' => __('finance.tx_debit'),
            default => $this->type,
        };
    }

    public function isEditable(): bool
    {
        if ($this->reference_type === null) {
            return true;
        }

        return in_array($this->reference_type, [
            Collection::class,
            Payment::class,
        ], true);
    }

    public function editUrl(): ?string
    {
        if ($this->reference instanceof Collection) {
            return route('finance.collections.edit', $this->reference);
        }

        if ($this->reference instanceof Payment) {
            return route('finance.payments.edit', $this->reference);
        }

        if ($this->reference_type === null) {
            return route('finance.transactions.edit', $this);
        }

        return null;
    }
}
