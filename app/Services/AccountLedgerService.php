<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;

class AccountLedgerService
{
    public function adjustBalance(
        Account $account,
        float $delta,
        ?object $reference = null,
        ?string $description = null,
        ?string $transactionDate = null,
        ?int $userId = null,
    ): void {
        if ($delta == 0.0) {
            return;
        }

        $account->increment('current_balance', $delta);

        AccountTransaction::create([
            'account_id' => $account->id,
            'type' => $delta >= 0 ? 'credit' : 'debit',
            'amount' => abs($delta),
            'currency' => $account->currency,
            'exchange_rate' => 1,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'description' => $description ?? 'Finans hareketi',
            'transaction_date' => $transactionDate ?? now()->toDateString(),
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    public function reverseBalance(
        Account $account,
        float $delta,
        ?object $reference = null,
        ?string $description = null,
        ?string $transactionDate = null,
        ?int $userId = null,
    ): void {
        $this->adjustBalance($account, -$delta, $reference, $description, $transactionDate, $userId);
    }
}
