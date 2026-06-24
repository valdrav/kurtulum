<?php

namespace App\Services;

use App\Models\Account;
use App\Models\IncomeExpense;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyTreasuryService
{
    public function ensureDefaults(): void
    {
        if (! Account::where('is_treasury', true)->exists()) {
            Account::create([
                'code' => 'GENEL-KASA',
                'name' => 'Ana Kasa (Nakit)',
                'type' => 'cash',
                'is_treasury' => true,
                'currency' => 'TRY',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'notes' => 'Şirket genel nakit kasası — siparişten bağımsız',
            ]);
        }

        if (! Account::where('is_treasury', true)->where('type', 'bank')->exists()) {
            Account::create([
                'code' => 'GENEL-BANKA',
                'name' => 'Ana Banka Hesabı',
                'type' => 'bank',
                'is_treasury' => true,
                'currency' => 'TRY',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'notes' => 'Şirket genel banka hesabı',
            ]);
        }
    }

    /** @return Collection<int, Account> */
    public function accounts(): Collection
    {
        $this->ensureDefaults();

        return Account::query()
            ->where('is_treasury', true)
            ->where('is_active', true)
            ->orderByRaw("CASE type WHEN 'cash' THEN 0 WHEN 'bank' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();
    }

    public function defaultAccount(): Account
    {
        $this->ensureDefaults();

        return Account::query()
            ->where('is_treasury', true)
            ->where('type', 'cash')
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOrFail();
    }

    public function totalBalanceTry(): float
    {
        return (float) $this->accounts()->sum('current_balance');
    }

    /** @return array{income: float, expense: float, net: float} */
    public function annualSummary(int $year): array
    {
        $income = (float) IncomeExpense::where('type', 'income')
            ->whereYear('transaction_date', $year)
            ->sum(DB::raw('COALESCE(amount_base, amount)'));

        $expense = (float) IncomeExpense::where('type', 'expense')
            ->whereYear('transaction_date', $year)
            ->sum(DB::raw('COALESCE(amount_base, amount)'));

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    /** @return Collection<int, array{month: int, income: float, expense: float, net: float}> */
    public function monthlyBreakdown(int $year): Collection
    {
        return collect(range(1, 12))->map(function (int $month) use ($year) {
            $income = (float) IncomeExpense::where('type', 'income')
                ->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year)
                ->sum(DB::raw('COALESCE(amount_base, amount)'));

            $expense = (float) IncomeExpense::where('type', 'expense')
                ->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year)
                ->sum(DB::raw('COALESCE(amount_base, amount)'));

            return [
                'month' => $month,
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        });
    }
}
