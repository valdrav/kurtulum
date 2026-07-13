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
use Illuminate\Support\Collection as SupportCollection;

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

        return (float) Account::query()
            ->companyTreasury()
            ->where('is_active', true)
            ->get()
            ->sum(fn (Account $account) => $this->accountBalanceInDefaultCurrency($account, $target));
    }

    public function accountBalanceInDefaultCurrency(Account $account, ?string $target = null): float
    {
        $target ??= $this->defaultCurrency();

        return $this->transactionAmountInDefaultCurrency(
            (float) $account->current_balance,
            strtoupper((string) ($account->currency ?? 'TRY')),
            null,
            $target
        );
    }

    /**
     * @return array{
     *     credit: float,
     *     debit: float,
     *     net: float,
     *     operational_credit: float,
     *     operational_debit: float,
     *     operational_net: float,
     *     count: int
     * }
     */
    public function periodSummary(Carbon $start, Carbon $end, ?int $accountId = null): array
    {
        $transactions = $this->baseQuery($accountId)
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->get();

        $credit = 0.0;
        $debit = 0.0;
        $net = 0.0;
        $operationalCredit = 0.0;
        $operationalDebit = 0.0;

        foreach ($transactions as $transaction) {
            $account = $transaction->account;

            if (! $account) {
                continue;
            }

            $amount = $this->transactionAmountInDefaultCurrency(
                (float) $transaction->amount,
                strtoupper((string) ($account->currency ?? 'TRY')),
                (float) ($transaction->exchange_rate ?? 0) ?: null,
            );

            $signed = $transaction->type === 'credit' ? $amount : -$amount;
            $net += $signed;

            if ($this->isReversalTransaction($transaction)) {
                continue;
            }

            if ($transaction->type === 'credit') {
                $credit += $amount;
                $operationalCredit += $amount;
            } else {
                $debit += $amount;
                $operationalDebit += $amount;
            }
        }

        return [
            'credit' => $credit,
            'debit' => $debit,
            'net' => $net,
            'operational_credit' => $operationalCredit,
            'operational_debit' => $operationalDebit,
            'operational_net' => $operationalCredit - $operationalDebit,
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

    public function treasuryAccountIds(): SupportCollection
    {
        return Account::query()
            ->companyTreasury()
            ->where('is_active', true)
            ->pluck('id');
    }

    protected function baseQuery(?int $accountId = null): Builder
    {
        return AccountTransaction::query()
            ->with([
                'account' => fn ($query) => $query->withTrashed()->select('id', 'uuid', 'name', 'currency', 'is_treasury', 'type'),
                'reference' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Collection::class => ['customer', 'account.customer'],
                    Payment::class => ['supplier', 'account.supplier'],
                    IncomeExpense::class => [],
                ]),
            ])
            ->whereIn('account_id', $this->treasuryAccountIds())
            ->when($accountId, fn (Builder $q) => $q->where('account_id', $accountId));
    }

    protected function transactionAmountInDefaultCurrency(
        float $amount,
        string $accountCurrency,
        ?float $lockedRate = null,
        ?string $target = null,
    ): float {
        $target ??= $this->defaultCurrency();
        $accountCurrency = strtoupper($accountCurrency);

        if ($accountCurrency === $target) {
            return round($amount, 2);
        }

        if ($lockedRate && $lockedRate > 0) {
            return round($amount * $lockedRate, 2);
        }

        $converted = $this->rates->convert($amount, $accountCurrency, $target);

        return round($converted ?? $amount, 2);
    }

    protected function isReversalTransaction(AccountTransaction $transaction): bool
    {
        $description = (string) $transaction->description;

        foreach (['İptal', 'iptal', 'Düzeltme iptali', 'Cancel'] as $prefix) {
            if (str_starts_with($description, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function signedAmount(AccountTransaction $transaction): float
    {
        $amount = (float) $transaction->amount;

        return $transaction->type === 'credit' ? $amount : -$amount;
    }
}
