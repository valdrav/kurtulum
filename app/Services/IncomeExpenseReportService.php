<?php

namespace App\Services;

use App\Models\IncomeExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomeExpenseReportService
{
    public const PERIODS = ['day', 'week', 'month', 'year'];

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
    public function summary(Carbon $start, Carbon $end): array
    {
        $base = $this->queryForRange($start, $end);
        $amountSql = DB::raw('COALESCE(amount_base, amount)');

        $income = (float) (clone $base)->where('type', 'income')->sum($amountSql);
        $expense = (float) (clone $base)->where('type', 'expense')->sum($amountSql);
        $incomeCount = (int) (clone $base)->where('type', 'income')->count();
        $expenseCount = (int) (clone $base)->where('type', 'expense')->count();

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'income_count' => $incomeCount,
            'expense_count' => $expenseCount,
            'total_count' => $incomeCount + $expenseCount,
        ];
    }

    /** @return Collection<int, object{category: string, type: string, total: float, count: int}> */
    public function byCategory(Carbon $start, Carbon $end, ?string $type = null): Collection
    {
        $query = $this->queryForRange($start, $end)
            ->select('category', 'type')
            ->selectRaw('SUM(COALESCE(amount_base, amount)) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category', 'type')
            ->orderByDesc('total');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get()->map(fn ($row) => (object) [
            'category' => finance_categories()->label($row->category),
            'type' => $row->type,
            'total' => (float) $row->total,
            'count' => (int) $row->count,
        ]);
    }

    /** @return Collection<int, object{account_name: string, income: float, expense: float, net: float}> */
    public function byTreasury(Carbon $start, Carbon $end): Collection
    {
        return $this->queryForRange($start, $end)
            ->whereNotNull('income_expenses.account_id')
            ->join('accounts', 'accounts.id', '=', 'income_expenses.account_id')
            ->where('accounts.is_treasury', true)
            ->select('accounts.name as account_name')
            ->selectRaw("SUM(CASE WHEN income_expenses.type = 'income' THEN COALESCE(income_expenses.amount_base, income_expenses.amount) ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN income_expenses.type = 'expense' THEN COALESCE(income_expenses.amount_base, income_expenses.amount) ELSE 0 END) as expense")
            ->groupBy('accounts.id', 'accounts.name')
            ->orderBy('accounts.name')
            ->get()
            ->map(fn ($row) => (object) [
                'account_name' => $row->account_name,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'net' => (float) $row->income - (float) $row->expense,
            ]);
    }

    /** @return Collection<int, array{label: string, income: float, expense: float, net: float}> */
    public function timeline(Carbon $start, Carbon $end, string $period): Collection
    {
        return match ($period) {
            'day' => collect([[
                'label' => $start->translatedFormat('d F Y'),
                'income' => $this->sumType($start, $end, 'income'),
                'expense' => $this->sumType($start, $end, 'expense'),
                'net' => $this->sumType($start, $end, 'income') - $this->sumType($start, $end, 'expense'),
            ]]),
            'week' => collect(range(0, 6))->map(function (int $offset) use ($start) {
                $day = $start->copy()->addDays($offset);

                return [
                    'label' => $day->translatedFormat('l d.m'),
                    'income' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'income'),
                    'expense' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'expense'),
                    'net' => $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'income')
                        - $this->sumType($day->copy()->startOfDay(), $day->copy()->endOfDay(), 'expense'),
                ];
            }),
            'year' => collect(range(1, 12))->map(function (int $month) use ($start) {
                $monthStart = $start->copy()->month($month)->startOfMonth();
                $monthEnd = $start->copy()->month($month)->endOfMonth();

                return [
                    'label' => $monthStart->translatedFormat('F'),
                    'income' => $this->sumType($monthStart, $monthEnd, 'income'),
                    'expense' => $this->sumType($monthStart, $monthEnd, 'expense'),
                    'net' => $this->sumType($monthStart, $monthEnd, 'income') - $this->sumType($monthStart, $monthEnd, 'expense'),
                ];
            }),
            default => $this->weeklyBucketsInMonth($start, $end),
        };
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

    protected function sumType(Carbon $start, Carbon $end, string $type): float
    {
        return (float) $this->queryForRange($start, $end)
            ->where('type', $type)
            ->sum(DB::raw('COALESCE(amount_base, amount)'));
    }

    /** @return Collection<int, array{label: string, income: float, expense: float, net: float}> */
    protected function weeklyBucketsInMonth(Carbon $start, Carbon $end): Collection
    {
        $buckets = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekStart = $cursor->copy();
            $weekEnd = $cursor->copy()->addDays(6);
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $buckets->push([
                'label' => $weekStart->format('d.m') . ' – ' . $weekEnd->format('d.m'),
                'income' => $this->sumType($weekStart, $weekEnd, 'income'),
                'expense' => $this->sumType($weekStart, $weekEnd, 'expense'),
                'net' => $this->sumType($weekStart, $weekEnd, 'income') - $this->sumType($weekStart, $weekEnd, 'expense'),
            ]);

            $cursor->addDays(7);
        }

        return $buckets;
    }
}
