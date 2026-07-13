<?php

namespace App\Services;

use App\Models\Account;
use App\Models\IncomeExpense;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyTreasuryService
{
    public const PRIMARY_CODE = 'GENEL-BANKA';

    public function ensureDefaults(): void
    {
        Account::firstOrCreate(
            ['code' => self::PRIMARY_CODE],
            [
                'name' => 'Şirket Banka Hesabı',
                'type' => 'bank',
                'is_treasury' => true,
                'currency' => 'TRY',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'notes' => 'Ana şirket banka hesabı — tahsilat ve ödemeler buraya yansır',
            ]
        );
    }

    /** @return Collection<int, Account> */
    public function accounts(): Collection
    {
        $this->ensureDefaults();

        return Account::query()
            ->companyTreasury()
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [self::PRIMARY_CODE])
            ->orderBy('name')
            ->get();
    }

    public function defaultAccount(): Account
    {
        $this->ensureDefaults();

        return Account::query()
            ->companyTreasury()
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN code = ? THEN 0 WHEN type = ? THEN 1 ELSE 2 END', [self::PRIMARY_CODE, 'bank'])
            ->orderBy('id')
            ->firstOrFail();
    }

    public function totalBalanceTry(): float
    {
        return app(TreasuryLedgerService::class)->totalBalanceInDefaultCurrency();
    }

    /** @return array{income: float, expense: float, net: float} */
    public function annualSummary(int $year): array
    {
        $income = (float) IncomeExpense::query()
            ->whereHas('account', fn ($q) => $q->companyTreasury())
            ->where('type', 'income')
            ->whereYear('transaction_date', $year)
            ->sum(DB::raw('COALESCE(amount_base, amount)'));

        $expense = (float) IncomeExpense::query()
            ->whereHas('account', fn ($q) => $q->companyTreasury())
            ->where('type', 'expense')
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
            $base = IncomeExpense::query()->whereHas('account', fn ($q) => $q->companyTreasury());

            $income = (float) (clone $base)
                ->where('type', 'income')
                ->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year)
                ->sum(DB::raw('COALESCE(amount_base, amount)'));

            $expense = (float) (clone $base)
                ->where('type', 'expense')
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
