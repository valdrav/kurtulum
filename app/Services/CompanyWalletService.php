<?php

namespace App\Services;

use App\Models\CompanyWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyWalletService
{
    public function ensureDefault(?int $userId = null): CompanyWallet
    {
        $userId = $userId ?? (int) auth()->id();
        $user = User::findOrFail($userId);

        $wallet = CompanyWallet::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($wallet) {
            return $wallet;
        }

        return CompanyWallet::create([
            'user_id' => $userId,
            'name' => __('finance.wallet_default_name', ['name' => $user->name]),
            'holder_name' => $user->name,
            'currency' => 'TRY',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'notes' => __('finance.wallet_default_note'),
        ]);
    }

    /** @return Collection<int, CompanyWallet> */
    public function wallets(?int $userId = null): Collection
    {
        $userId = $userId ?? (int) auth()->id();
        $this->ensureDefault($userId);

        return CompanyWallet::query()
            ->with('user')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function totalBalance(?int $userId = null): float
    {
        $userId = $userId ?? (int) auth()->id();

        return (float) CompanyWallet::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->sum('current_balance');
    }

    /** @return array<int> */
    public function walletIdsForUser(?int $userId = null): array
    {
        $userId = $userId ?? (int) auth()->id();

        return CompanyWallet::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('id')
            ->all();
    }

    /** @return array{deposit: float, expense: float, net: float} */
    public function annualSummary(?int $walletId, int $year, ?int $userId = null): array
    {
        $walletIds = $walletId ? [$walletId] : $this->walletIdsForUser($userId);

        if ($walletIds === []) {
            return ['deposit' => 0.0, 'expense' => 0.0, 'net' => 0.0];
        }

        $deposit = (float) WalletTransaction::query()
            ->whereIn('company_wallet_id', $walletIds)
            ->whereYear('transaction_date', $year)
            ->where('type', 'deposit')
            ->sum('amount');

        $expense = (float) WalletTransaction::query()
            ->whereIn('company_wallet_id', $walletIds)
            ->whereYear('transaction_date', $year)
            ->where('type', 'expense')
            ->sum('amount');

        return [
            'deposit' => $deposit,
            'expense' => $expense,
            'net' => $deposit - $expense,
        ];
    }

    public function recordTransaction(
        CompanyWallet $wallet,
        string $type,
        float $amount,
        string $description,
        string $transactionDate,
        ?string $counterparty = null,
        ?string $receiptNo = null,
        ?string $notes = null,
        ?int $userId = null,
    ): WalletTransaction {
        return DB::transaction(function () use (
            $wallet, $type, $amount, $description, $transactionDate,
            $counterparty, $receiptNo, $notes, $userId
        ) {
            $entry = WalletTransaction::create([
                'company_wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'description' => $description,
                'counterparty' => $counterparty,
                'receipt_no' => $receiptNo,
                'notes' => $notes,
                'transaction_date' => $transactionDate,
                'user_id' => $userId ?? auth()->id(),
            ]);

            $delta = $type === 'deposit' ? $amount : -$amount;
            $wallet->increment('current_balance', $delta);

            return $entry;
        });
    }

    public function reverseTransaction(WalletTransaction $entry): void
    {
        DB::transaction(function () use ($entry) {
            $wallet = $entry->wallet;

            if ($wallet) {
                $delta = $entry->type === 'deposit' ? -$entry->amount : $entry->amount;
                $wallet->increment('current_balance', $delta);
            }

            $entry->delete();
        });
    }
}
