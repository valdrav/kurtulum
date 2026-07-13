<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Collection;
use App\Models\IncomeExpense;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class TreasuryLedgerService
{
    public function __construct(
        protected CompanyTreasuryService $treasury,
        protected ExchangeRateService $rates,
    ) {}

    public function defaultCurrency(): string
    {
        $currency = registry()->defaultCurrency();

        return strtoupper((string) ($currency?->code ?? config('ticari.default_currency', 'TRY')));
    }

    public function totalBalanceInDefaultCurrency(): float
    {
        $target = $this->defaultCurrency();

        return (float) $this->treasury->accounts()->sum(function (Account $account) use ($target) {
            return $this->amountInCurrency((float) $account->current_balance, $account->currency, $target);
        });
    }

    public function amountInCurrency(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        $converted = $this->rates->convert($amount, $from, $to);

        return $converted ?? $amount;
    }

    /** @return array{credit: float, debit: float, net: float, count: int} */
    public function periodSummary(Carbon $start, Carbon $end, ?int $accountId = null): array
    {
        $target = $this->defaultCurrency();
        $transactions = $this->baseQuery($accountId)
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->get(['type', 'amount', 'currency', 'exchange_rate']);

        $credit = 0.0;
        $debit = 0.0;

        foreach ($transactions as $transaction) {
            $amount = $this->amountInCurrency((float) $transaction->amount, (string) $transaction->currency, $target);

            if ($transaction->type === 'credit') {
                $credit += $amount;
            } else {
                $debit += $amount;
            }
        }

        return [
            'credit' => $credit,
            'debit' => $debit,
            'net' => $credit - $debit,
            'count' => $transactions->count(),
        ];
    }

    public function paginateTransactions(Request $request, Carbon $start, Carbon $end): LengthAwarePaginator
    {
        $accountId = $request->filled('account_id') ? (int) $request->account_id : null;

        return $this->baseQuery($accountId)
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->when($request->ledger_type, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($request->search, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhereHas('account', fn (Builder $a) => $a->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();
    }

    /** @return array<int, float> */
    public function runningBalancesAfter(Account $account, LengthAwarePaginator $page): array
    {
        $running = (float) $account->current_balance;
        $skip = max(0, ($page->currentPage() - 1) * $page->perPage());

        if ($skip > 0) {
            $newer = AccountTransaction::query()
                ->where('account_id', $account->id)
                ->latest('transaction_date')
                ->latest('id')
                ->limit($skip)
                ->get();

            foreach ($newer as $transaction) {
                $running -= $this->signedAmount($transaction);
            }
        }

        $map = [];

        foreach ($page->items() as $transaction) {
            if (! $transaction instanceof AccountTransaction) {
                continue;
            }

            $map[$transaction->id] = $running;
            $running -= $this->signedAmount($transaction);
        }

        return $map;
    }

    public function sourceLabel(AccountTransaction $transaction): string
    {
        $reference = $transaction->relationLoaded('reference')
            ? $transaction->reference
            : $transaction->reference()->first();

        if ($reference instanceof Collection) {
            return __('finance.ledger_source_collection');
        }

        if ($reference instanceof Payment) {
            return __('finance.ledger_source_payment');
        }

        if ($reference instanceof IncomeExpense) {
            return $reference->type === 'income'
                ? __('finance.ledger_source_income')
                : __('finance.ledger_source_expense');
        }

        if ($transaction->reference_type === null) {
            if (str_contains((string) $transaction->description, 'açılış') || str_contains((string) $transaction->description, 'Açılış')) {
                return __('finance.ledger_source_opening');
            }

            return __('finance.ledger_source_manual');
        }

        return class_basename((string) $transaction->reference_type);
    }

    protected function baseQuery(?int $accountId = null): Builder
    {
        $treasuryIds = $this->treasury->accounts()->pluck('id');

        return AccountTransaction::query()
            ->with([
                'account:id,name,currency,is_treasury',
                'reference' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Collection::class => ['customer', 'account.customer'],
                    Payment::class => ['supplier', 'account.supplier'],
                    IncomeExpense::class => [],
                ]),
            ])
            ->whereIn('account_id', $treasuryIds)
            ->when($accountId, fn (Builder $q) => $q->where('account_id', $accountId));
    }

    protected function signedAmount(AccountTransaction $transaction): float
    {
        $amount = (float) $transaction->amount;

        return $transaction->type === 'credit' ? $amount : -$amount;
    }
}
