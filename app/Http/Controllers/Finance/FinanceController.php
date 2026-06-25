<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\SystemCurrency;
use App\Services\AccountLedgerService;
use App\Services\CompanyTreasuryService;
use App\Services\IncomeExpenseReportService;
use App\Services\OrderFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function __construct(
        protected OrderFinanceService $orderFinance,
        protected AccountLedgerService $ledger,
    ) {
        $this->middleware('permission:finance.view')->only([
            'index', 'accounts', 'showAccount', 'payments', 'collections',
            'incomeExpenses', 'profitLoss', 'showPayment', 'showCollection', 'treasury',
        ]);
        $this->middleware('permission:finance.create')->only([
            'createAccount', 'storeAccount', 'storePayment', 'storeCollection', 'storeIncomeExpense',
            'storeTreasuryAccount',
        ]);
        $this->middleware('permission:finance.edit')->only([
            'editAccount', 'updateAccount', 'editPayment', 'updatePayment',
            'editCollection', 'updateCollection', 'editIncomeExpense', 'updateIncomeExpense',
        ]);
        $this->middleware('permission:finance.delete|finance.create')->only([
            'destroyPayment', 'destroyCollection', 'destroyIncomeExpense',
        ]);
    }

    public function index()
    {
        return redirect()->route('finance.treasury');
    }

    public function treasury(Request $request, CompanyTreasuryService $treasury)
    {
        $year = (int) ($request->year ?: now()->year);
        $treasuryAccounts = $treasury->accounts();
        $summary = $treasury->annualSummary($year);
        $months = $treasury->monthlyBreakdown($year);
        $totalCash = $treasury->totalBalanceTry();

        $recentEntries = IncomeExpense::with('account')
            ->whereYear('transaction_date', $year)
            ->latest('transaction_date')
            ->latest('id')
            ->limit(20)
            ->get();

        $paymentMethods = finance_categories()->paymentMethods();
        $defaultTreasury = $treasury->defaultAccount();

        return view('finance.treasury.index', compact(
            'year', 'treasuryAccounts', 'summary', 'months', 'totalCash',
            'recentEntries', 'paymentMethods', 'defaultTreasury'
        ));
    }

    public function storeTreasuryAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:bank,cash',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['code'] = $this->generateNumber('KSA');
        $validated['is_treasury'] = true;
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['current_balance'] = $validated['opening_balance'];
        $validated['is_active'] = true;

        $account = Account::create($validated);

        if ($validated['opening_balance'] != 0) {
            AccountTransaction::create([
                'account_id' => $account->id,
                'type' => $validated['opening_balance'] >= 0 ? 'credit' : 'debit',
                'amount' => abs($validated['opening_balance']),
                'currency' => $account->currency,
                'exchange_rate' => 1,
                'description' => 'Genel kasa açılış bakiyesi',
                'transaction_date' => now()->toDateString(),
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('finance.treasury')->with('success', __('messages.created'));
    }

    public function accounts(Request $request)
    {
        $accounts = Account::with(['customer', 'supplier'])
            ->cari()
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->q, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%");
            }))
            ->latest()->paginate(20);

        return view('finance.accounts.index', compact('accounts'));
    }

    public function createAccount()
    {
        return view('finance.accounts.form', [
            'account' => new Account(['currency' => 'TRY', 'is_active' => true]),
            'customers' => Customer::orderBy('company_name')->get(),
            'suppliers' => Supplier::orderBy('company_name')->get(),
        ]);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:32|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,bank,cash,expense,income',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_treasury' => 'boolean',
        ]);

        $validated['is_treasury'] = $request->boolean('is_treasury');
        if ($validated['is_treasury']) {
            $validated['customer_id'] = null;
            $validated['supplier_id'] = null;
        }

        $validated['code'] = $validated['code'] ?? $this->generateNumber('ACC');
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['current_balance'] = $validated['opening_balance'];
        $validated['is_active'] = $request->boolean('is_active', true);

        $account = Account::create($validated);

        if ($validated['opening_balance'] != 0) {
            AccountTransaction::create([
                'account_id' => $account->id,
                'type' => $validated['opening_balance'] >= 0 ? 'credit' : 'debit',
                'amount' => abs($validated['opening_balance']),
                'currency' => $account->currency,
                'exchange_rate' => 1,
                'description' => 'Açılış bakiyesi',
                'transaction_date' => now()->toDateString(),
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('finance.accounts.show', $account)->with('success', __('messages.created'));
    }

    public function editAccount(Account $account)
    {
        return view('finance.accounts.form', [
            'account' => $account,
            'customers' => Customer::orderBy('company_name')->get(),
            'suppliers' => Supplier::orderBy('company_name')->get(),
        ]);
    }

    public function updateAccount(Request $request, Account $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,bank,cash,expense,income',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_treasury' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_treasury'] = $request->boolean('is_treasury');
        if ($validated['is_treasury']) {
            $validated['customer_id'] = null;
            $validated['supplier_id'] = null;
        }
        $account->update($validated);

        return redirect()->route('finance.accounts.show', $account)->with('success', __('messages.saved'));
    }

    public function showAccount(Account $account)
    {
        $account->load(['customer', 'supplier', 'transactions' => fn ($q) => $q->latest()->limit(50)]);

        return view('finance.accounts.show', compact('account'));
    }

    public function payments(Request $request)
    {
        $payments = Payment::with(['account', 'paymentMethod'])
            ->when($request->q, fn ($q, $s) => $q->where('payment_number', 'like', "%{$s}%"))
            ->latest()->paginate(20);
        $paymentMethods = payment_methods()->forPayment();
        $accounts = Account::query()->cari()->where('is_active', true)->orderBy('name')->get();
        $treasuryAccounts = company_treasury()->accounts();

        return view('finance.payments.index', compact('payments', 'paymentMethods', 'accounts', 'treasuryAccounts'));
    }

    public function showPayment(Payment $payment)
    {
        $payment->load(['account', 'paymentMethod', 'user']);

        return view('finance.payments.show', compact('payment'));
    }

    public function editPayment(Payment $payment)
    {
        $paymentMethods = payment_methods()->forPayment();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();

        return view('finance.payments.form', compact('payment', 'paymentMethods', 'accounts'));
    }

    public function updatePayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'payment_date' => 'required|date',
        ]);

        $payment->update($validated);

        return redirect()->route('finance.payments.show', $payment)->with('success', __('messages.saved'));
    }

    public function destroyPayment(Payment $payment)
    {
        $this->orderFinance->reversePayment($payment);

        return redirect()->route('finance.payments')->with('success', __('messages.deleted'));
    }

    public function storePayment(Request $request)
    {
        $method = \App\Models\PaymentMethod::findOrFail($request->input('payment_method_id'));
        $validated = $request->validate(array_merge(
            payment_methods()->buildValidationRules($method),
            [
                'order_id' => 'nullable|exists:orders,id',
                'treasury_account_id' => 'nullable|exists:accounts,id',
            ]
        ));
        $validated = hook()->filter('payment.before_create', $validated, $method);

        $order = ! empty($validated['order_id'])
            ? \App\Models\Order::find($validated['order_id'])
            : null;

        $fee = payment_methods()->calculateFee($method, (float) $validated['amount']);
        $this->orderFinance->recordPayment($validated, $method, $fee, $order);

        $redirect = $order
            ? redirect()->route('orders.show', $order)
            : back();

        return $redirect->with('success', __('messages.created'));
    }

    public function collections(Request $request)
    {
        $collections = Collection::with(['account', 'paymentMethod'])
            ->when($request->q, fn ($q, $s) => $q->where('collection_number', 'like', "%{$s}%"))
            ->latest()->paginate(20);
        $paymentMethods = payment_methods()->forCollection();
        $accounts = Account::query()->cari()->where('is_active', true)->orderBy('name')->get();
        $treasuryAccounts = company_treasury()->accounts();

        return view('finance.collections.index', compact('collections', 'paymentMethods', 'accounts', 'treasuryAccounts'));
    }

    public function showCollection(Collection $collection)
    {
        $collection->load(['account', 'paymentMethod', 'user']);

        return view('finance.collections.show', compact('collection'));
    }

    public function editCollection(Collection $collection)
    {
        $paymentMethods = payment_methods()->forCollection();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();

        return view('finance.collections.form', compact('collection', 'paymentMethods', 'accounts'));
    }

    public function updateCollection(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'collection_date' => 'required|date',
        ]);

        $collection->update($validated);

        return redirect()->route('finance.collections.show', $collection)->with('success', __('messages.saved'));
    }

    public function destroyCollection(Collection $collection)
    {
        $this->orderFinance->reverseCollection($collection);

        return redirect()->route('finance.collections')->with('success', __('messages.deleted'));
    }

    public function storeCollection(Request $request)
    {
        $method = \App\Models\PaymentMethod::findOrFail($request->input('payment_method_id'));
        $rules = payment_methods()->buildValidationRules($method);
        $rules['collection_date'] = $rules['payment_date'];
        unset($rules['payment_date']);
        $validated = $request->validate(array_merge($rules, [
            'order_id' => 'nullable|exists:orders,id',
            'treasury_account_id' => 'nullable|exists:accounts,id',
        ]));
        $validated = hook()->filter('collection.before_create', $validated, $method);

        $order = ! empty($validated['order_id'])
            ? \App\Models\Order::find($validated['order_id'])
            : null;

        $fee = payment_methods()->calculateFee($method, (float) $validated['amount']);
        $this->orderFinance->recordCollection($validated, $method, $fee, $order);

        $redirect = $order
            ? redirect()->route('orders.show', $order)
            : back();

        return $redirect->with('success', __('messages.created'));
    }

    public function incomeExpenses(Request $request, IncomeExpenseReportService $reports)
    {
        if ($request->filled('year') && ! $request->filled('period')) {
            $request->merge([
                'period' => 'year',
                'date' => $request->input('year') . '-06-15',
            ]);
        }

        $periodMeta = $reports->resolvePeriod(
            $request->input('period', 'month'),
            $request->input('date'),
            $request->input('from'),
            $request->input('to')
        );

        $items = IncomeExpense::with('account')
            ->whereDate('transaction_date', '>=', $periodMeta['start']->toDateString())
            ->whereDate('transaction_date', '<=', $periodMeta['end']->toDateString())
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('item_name', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%")
                        ->orWhere('category', 'like', "%{$s}%")
                        ->orWhere('vendor', 'like', "%{$s}%")
                        ->orWhere('receipt_no', 'like', "%{$s}%")
                        ->orWhere('notes', 'like', "%{$s}%");
                });
            })
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $summary = $reports->summary($periodMeta['start'], $periodMeta['end']);
        $treasuryAccounts = company_treasury()->accounts();
        $defaultTreasury = company_treasury()->defaultAccount();
        $paymentMethods = finance_categories()->paymentMethods();

        return view('finance.income-expenses.index', compact(
            'items', 'summary', 'treasuryAccounts', 'defaultTreasury', 'paymentMethods',
            'periodMeta'
        ));
    }

    public function storeIncomeExpense(Request $request)
    {
        $validated = $this->validateIncomeExpense($request);
        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated) {
            $entry = IncomeExpense::create($validated);
            $this->applyIncomeExpenseToTreasury($entry);
        });

        $redirect = $request->input('redirect_to') === 'treasury'
            ? redirect()->route('finance.treasury')
            : back();

        return $redirect->with('success', __('messages.created'));
    }

    public function editIncomeExpense(IncomeExpense $incomeExpense)
    {
        $treasuryAccounts = company_treasury()->accounts();
        $defaultTreasury = company_treasury()->defaultAccount();
        $paymentMethods = finance_categories()->paymentMethods();

        return view('finance.income-expenses.form', compact(
            'incomeExpense', 'treasuryAccounts', 'defaultTreasury', 'paymentMethods'
        ));
    }

    public function updateIncomeExpense(Request $request, IncomeExpense $incomeExpense)
    {
        $validated = $this->validateIncomeExpense($request);

        DB::transaction(function () use ($incomeExpense, $validated) {
            $this->reverseIncomeExpenseFromTreasury($incomeExpense);
            $incomeExpense->update($validated);
            $this->applyIncomeExpenseToTreasury($incomeExpense->fresh());
        });

        return redirect()->route('finance.income-expenses')->with('success', __('messages.saved'));
    }

    public function destroyIncomeExpense(IncomeExpense $incomeExpense)
    {
        DB::transaction(function () use ($incomeExpense) {
            $this->reverseIncomeExpenseFromTreasury($incomeExpense);
            $incomeExpense->delete();
        });

        return back()->with('success', __('messages.deleted'));
    }

    public function profitLoss(Request $request, IncomeExpenseReportService $reports)
    {
        $periodMeta = $reports->resolvePeriod(
            $request->input('period', 'month'),
            $request->input('date'),
            $request->input('from'),
            $request->input('to')
        );

        $summary = $reports->summary($periodMeta['start'], $periodMeta['end']);
        $timeline = $reports->timeline($periodMeta['start'], $periodMeta['end'], $periodMeta['period']);
        $byCategory = $reports->byCategory($periodMeta['start'], $periodMeta['end']);
        $byTreasury = $reports->byTreasury($periodMeta['start'], $periodMeta['end']);
        $entries = IncomeExpense::with(['account', 'user'])
            ->whereDate('transaction_date', '>=', $periodMeta['start']->toDateString())
            ->whereDate('transaction_date', '<=', $periodMeta['end']->toDateString())
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return view('finance.reports.profit-loss', compact(
            'periodMeta', 'summary', 'timeline', 'byCategory', 'byTreasury', 'entries'
        ));
    }

    protected function validateIncomeExpense(Request $request): array
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'item_name' => 'required|string|max:200',
            'vendor' => 'nullable|string|max:200',
            'payment_method' => 'nullable|string|max:50',
            'receipt_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'account_id' => 'nullable|exists:accounts,id',
            'transaction_date' => 'required|date',
        ]);

        $validated['category'] = finance_categories()->infer(
            $validated['type'],
            $validated['item_name'],
            $validated['vendor'] ?? null,
            $validated['notes'] ?? null,
        );
        $validated['description'] = trim($validated['item_name']);
        if (! empty($validated['vendor'])) {
            $validated['description'] .= ' · ' . $validated['vendor'];
        }

        $currency = SystemCurrency::where('code', $validated['currency'])->first();
        $validated['exchange_rate'] = $validated['exchange_rate'] ?? ($currency?->tcmb_rate ?? $currency?->exchange_rate ?? 1);
        $validated['amount_base'] = round($validated['amount'] * $validated['exchange_rate'], 2);

        $validated['account_id'] = $this->resolveTreasuryAccountId($validated['account_id'] ?? null);

        return $validated;
    }

    protected function resolveTreasuryAccountId(?int $accountId): int
    {
        $treasury = company_treasury();

        if ($accountId) {
            $account = Account::query()->whereKey($accountId)->where('is_treasury', true)->first();

            if (! $account) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'account_id' => [__('finance.treasury_account_required')],
                ]);
            }

            return $account->id;
        }

        return $treasury->defaultAccount()->id;
    }

    protected function applyIncomeExpenseToTreasury(IncomeExpense $entry): void
    {
        if (! $entry->account_id) {
            return;
        }

        $account = Account::find($entry->account_id);

        if (! $account?->is_treasury) {
            return;
        }

        $amount = $this->treasuryEntryAmount($entry, $account);
        $delta = $entry->type === 'income' ? $amount : -$amount;

        $this->ledger->adjustBalance(
            $account,
            $delta,
            $entry,
            ($entry->type === 'income' ? 'Gelir: ' : 'Gider: ') . $entry->displayTitle(),
            $entry->transaction_date->toDateString()
        );
    }

    protected function reverseIncomeExpenseFromTreasury(IncomeExpense $entry): void
    {
        if (! $entry->account_id) {
            return;
        }

        $account = Account::find($entry->account_id);

        if (! $account?->is_treasury) {
            return;
        }

        $amount = $this->treasuryEntryAmount($entry, $account);
        $delta = $entry->type === 'income' ? -$amount : $amount;

        $this->ledger->adjustBalance(
            $account,
            $delta,
            $entry,
            'İptal: ' . $entry->displayTitle(),
            now()->toDateString()
        );
    }

    protected function treasuryEntryAmount(IncomeExpense $entry, Account $account): float
    {
        if ($account->currency === $entry->currency) {
            return (float) $entry->amount;
        }

        return (float) ($entry->amount_base ?? $entry->amount);
    }
}
