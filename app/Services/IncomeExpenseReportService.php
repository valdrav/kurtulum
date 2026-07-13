<?php

namespace App\Services;

use App\Models\IncomeExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class IncomeExpenseReportService
{
    public const PERIODS = ['day', 'week', 'month', 'year'];

    public function __construct(
        protected TreasuryLedgerService $ledger,
    ) {}

    /** @return array{period: string, start: Carbon, end: Carbon, label: string} */
    public function resolvePeriod(string $period, ?string $anchor = null, ?string $from = null, ?string $to = null): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : 'month';
        $anchorDate = $anchor ? Carbon::parse($anchor) : now();

        if ($from && $to) {
            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();

            return [
                'period' => 'custom',
                'start' => $start,
                'end' => $end,
                'label' => $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y'),
            ];
        }

        return match ($period) {
            'day' => [
                'period' => 'day',
                'start' => $anchorDate->copy()->startOfDay(),
                'end' => $anchorDate->copy()->endOfDay(),
                'label' => $anchorDate->translatedFormat('d F Y'),
            ],
            'week' => [
                'period' => 'week',
                'start' => $anchorDate->copy()->startOfWeek(Carbon::MONDAY),
                'end' => $anchorDate->copy()->endOfWeek(Carbon::SUNDAY),
                'label' => $anchorDate->copy()->startOfWeek(Carbon::MONDAY)->format('d.m.Y')
                    . ' – '
                    . $anchorDate->copy()->endOfWeek(Carbon::SUNDAY)->format('d.m.Y'),
            ],
            'year' => [
                'period' => 'year',
                'start' => $anchorDate->copy()->startOfYear(),
                'end' => $anchorDate->copy()->endOfYear(),
                'label' => (string) $anchorDate->year,
            ],
            default => [
                'period' => 'month',
                'start' => $anchorDate->copy()->startOfMonth(),
                'end' => $anchorDate->copy()->endOfMonth(),
                'label' => $anchorDate->translatedFormat('F Y'),
            ],
        };
    }

    public function queryForRange(Carbon $start, Carbon $end): Builder
    {
        return IncomeExpense::query()
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString());
    }

    /** @return array{income: float, expense: float, net: float, income_count: int, expense_count: int, total_count: int} */
    public function summary(Carbon $start, Carbon $end, ?int $accountId = null): array
    {
        $ledger = $this->ledger->periodSummary($start, $end, $accountId);
        $transactions = $this->ledger->transactionsInRange($start, $end, $accountId);
        $orphans = $this->orphanIncomeExpenseTotals($start, $end);

        $income = $ledger['operational_credit'] + $orphans['income'];
        $expense = $ledger['operational_debit'] + $orphans['expense'];
        $incomeCount = $transactions->where('type', 'credit')->count() + $orphans['income_count'];
        $expenseCount = $transactions->where('type', 'debit')->count() + $orphans['expense_count'];

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'income_count' => $incomeCount,
            'expense_count' => $expenseCount,
            'total_count' => $incomeCount + $expenseCount,
        ];
    }

    /** @return SupportCollection<int, object{category: string, type: string, total: float, count: int}> */
    public function byCategory(Carbon $start, Carbon $end, ?string $type = null, ?int $accountId = null): SupportCollection
    {
        $groups = [];

        foreach ($this->ledger->transactionsInRange($start, $end, $accountId) as $transaction) {
            $meta = $this->ledger->categoryKey($transaction);
            $entryType = $meta['type'];
            $amount = $this->ledger->amountInDefaultCurrency($transaction);

            if ($type && $entryType !== $type) {
                continue;
            }

            $key = $meta['category'] . '|' . $entryType;
            $groups[$key] ??= [
                'category' => $meta['category'],
                'type' => $entryType,
                'total' => 0.0,
                'count' => 0,
            ];
            $groups[$key]['total'] += $amount;
            $groups[$key]['count']++;
        }

        foreach ($this->orphanIncomeExpenses($start, $end) as $entry) {
            if ($type && $entry->type !== $type) {
                continue;
            }

            $key = $entry->categoryLabel() . '|' . $entry->type;
            $groups[$key] ??= [
                'category' => $entry->categoryLabel(),
                'type' => $entry->type,
                'total' => 0.0,
                'count' => 0,
            ];
            $groups[$key]['total'] += $entry->normalizedAmount();
            $groups[$key]['count']++;
        }

        return collect($groups)
            ->sortByDesc('total')
            ->values()
            ->map(fn (array $row) => (object) $row);
    }

    /** @return SupportCollection<int, object{account_name: string, income: float, expense: float, net: float}> */
    public function byTreasury(Carbon $start, Carbon $end, ?int $accountId = null): SupportCollection
    {
        $groups = [];

        foreach ($this->ledger->transactionsInRange($start, $end, $accountId) as $transaction) {
            $accountName = $transaction->account?->name ?? __('finance.treasury_account');
            $amount = $this->ledger->amountInDefaultCurrency($transaction);

            $groups[$accountName] ??= [
                'account_name' => $accountName,
                'income' => 0.0,
                'expense' => 0.0,
            ];

            if ($transaction->type === 'credit') {
                $groups[$accountName]['income'] += $amount;
            } else {
                $groups[$accountName]['expense'] += $amount;
            }
        }

        return collect($groups)
            ->sortBy('account_name')
            ->values()
            ->map(fn (array $row) => (object) [
                'account_name' => $row['account_name'],
                'income' => $row['income'],
                'expense' => $row['expense'],
                'net' => $row['income'] - $row['expense'],
            ]);
    }

    /** @return SupportCollection<int, array{label: string, income: float, expense: float, net: float}> */
    public function timeline(Carbon $start, Carbon $end, string $period, ?int $accountId = null): SupportCollection
    {
        return match ($period) {
            'day' => collect([[
                'label' => $start->translatedFormat('d F Y'),
                'income' => $this->sumType($start, $end, 'income', $accountId),
                'expense' => $this->sumType($start, $end, 'expense', $accountId),
                'net' => $this->sumType($start, $end, 'income', $accountId) - $this->sumType($start, $end, 'expense', $accountId),
            ]]),
            'week' => collect(range(0, 6))->map(function (int $offset) use ($start, $accountId) {
                $day = $start->copy()->addDays($offset);

                return [
                    'label' => $day->translatedFormat('l d.m'),
                    'income' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'income', $accountId),
                    'expense' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'expense', $accountId),
                    'net' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'income', $accountId)
                        - $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'expense', $accountId),
                ];
            }),
            'year' => collect(range(1, 12))->map(function (int $month) use ($start, $accountId) {
                $monthStart = $start->copy()->month($month)->startOfMonth();
                $monthEnd = $start->copy()->month($month)->endOfMonth();

                return [
                    'label' => $monthStart->translatedFormat('F'),
                    'income' => $this->sumType($monthStart, $monthEnd, 'income', $accountId),
                    'expense' => $this->sumType($monthStart, $monthEnd, 'expense', $accountId),
                    'net' => $this->sumType($monthStart, $monthEnd, 'income', $accountId)
                        - $this->sumType($monthStart, $monthEnd, 'expense', $accountId),
                ];
            }),
            default => $this->weeklyBucketsInMonth($start, $end, $accountId),
        };
    }

    public function amountExpression()
    {
        return DB::raw($this->amountExpressionSql());
    }

    public function amountExpressionSql(string $table = ''): string
    {
        $prefix = $table !== '' ? $table.'.' : '';

        return 'COALESCE(NULLIF('.$prefix.'amount_base, 0), ROUND('.$prefix.'amount * COALESCE(NULLIF('.$prefix.'exchange_rate, 0), 1), 2))';
    }

    /** @return list<string> */
    public function distinctCategories(): array
    {
        return IncomeExpense::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->map(fn ($c) => finance_categories()->label($c))
            ->unique()
            ->values()
            ->all();
    }

    protected function sumType(Carbon $start, Carbon $end, string $type, ?int $accountId = null): float
    {
        $summary = $this->summary($start, $end, $accountId);

        return $type === 'income' ? $summary['income'] : $summary['expense'];
    }

    /** @return SupportCollection<int, array{label: string, income: float, expense: float, net: float}> */
    protected function weeklyBucketsInMonth(Carbon $start, Carbon $end, ?int $accountId = null): SupportCollection
    {
        $buckets = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addDays(6);
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $income = $this->sumType($weekStart, $weekEnd, 'income', $accountId);
            $expense = $this->sumType($weekStart, $weekEnd, 'expense', $accountId);

            $buckets->push([
                'label' => $weekStart->format('d.m') . ' – ' . $weekEnd->format('d.m'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ]);

            $cursor->addDays(7);
        }

        return $buckets;
    }

    /** @return array{income: float, expense: float, income_count: int, expense_count: int} */
    protected function orphanIncomeExpenseTotals(Carbon $start, Carbon $end): array
    {
        $entries = $this->orphanIncomeExpenses($start, $end);

        return [
            'income' => (float) $entries->where('type', 'income')->sum(fn (IncomeExpense $entry) => $entry->normalizedAmount()),
            'expense' => (float) $entries->where('type', 'expense')->sum(fn (IncomeExpense $entry) => $entry->normalizedAmount()),
            'income_count' => $entries->where('type', 'income')->count(),
            'expense_count' => $entries->where('type', 'expense')->count(),
        ];
    }

    /** @return SupportCollection<int, IncomeExpense> */
    protected function orphanIncomeExpenses(Carbon $start, Carbon $end): SupportCollection
    {
        $linkedIds = DB::table('account_transactions')
            ->where('reference_type', IncomeExpense::class)
            ->whereNotNull('reference_id')
            ->pluck('reference_id');

        $query = IncomeExpense::query()
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString());

        if ($linkedIds->isNotEmpty()) {
            $query->whereNotIn('id', $linkedIds);
        }

        return $query->get();
    }
}
