<?php

namespace App\Services;

use App\Models\CompanyWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyWalletService
{
    public function ensureDefault(): CompanyWallet
    {
        $wallet = CompanyWallet::query()->where('is_active', true)->orderBy('id')->first();

        if ($wallet) {
            return $wallet;
        }

        return CompanyWallet::create([
            'name' => 'Şirket Avans Hesabı',
            'holder_name' => null,
            'currency' => 'TRY',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'notes' => 'Şirket adına kişisel hesaba aktarılan avans bakiyesi',
        ]);
    }

    /** @return Collection<int, CompanyWallet> */
    public function wallets(): Collection
    {
        $this->ensureDefault();

        return CompanyWallet::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function totalBalance(): float
    {
        return (float) CompanyWallet::query()
            ->where('is_active', true)
            ->sum('current_balance');
    }

    /** @return array{deposit: float, expense: float, net: float} */
    public function annualSummary(?int $walletId, int $year): array
    {
        $depositQuery = WalletTransaction::query()
            ->whereYear('transaction_date', $year)
            ->where('type', 'deposit');

        $expenseQuery = WalletTransaction::query()
            ->whereYear('transaction_date', $year)
            ->where('type', 'expense');

        if ($walletId) {
            $depositQuery->where('company_wallet_id', $walletId);
            $expenseQuery->where('company_wallet_id', $walletId);
        }

        $deposit = (float) $depositQuery->sum('amount');
        $expense = (float) $expenseQuery->sum('amount');

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

    public function replaceTransaction(WalletTransaction $entry, array $data): WalletTransaction
    {
        return DB::transaction(function () use ($entry, $data) {
            $this->reverseTransaction($entry);

            $wallet = CompanyWallet::findOrFail($data['company_wallet_id']);

            return $this->recordTransaction(
                $wallet,
                $data['type'],
                (float) $data['amount'],
                $data['description'],
                $data['transaction_date'],
                $data['counterparty'] ?? null,
                $data['receipt_no'] ?? null,
                $data['notes'] ?? null,
                $entry->user_id,
            );
        });
    }
}
