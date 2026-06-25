<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CompanyWallet;
use App\Models\WalletTransaction;
use App\Services\CompanyWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function __construct(protected CompanyWalletService $wallets)
    {
        $this->middleware('permission:finance.view')->only(['index']);
        $this->middleware('permission:finance.create')->only(['storeWallet', 'storeTransaction']);
        $this->middleware('permission:finance.edit')->only(['updateTransaction']);
        $this->middleware('permission:finance.delete|finance.create')->only(['destroyTransaction']);
    }

    public function index(Request $request)
    {
        $year = (int) ($request->year ?: now()->year);
        $walletList = $this->wallets->wallets();
        $selectedWallet = $this->resolveWallet($request, $walletList);
        $walletId = $selectedWallet?->id;

        $summary = $this->wallets->annualSummary($walletId, $year);
        $totalBalance = $walletId
            ? (float) $selectedWallet->current_balance
            : $this->wallets->totalBalance();

        $transactions = WalletTransaction::with(['wallet', 'user'])
            ->when($walletId, fn ($q) => $q->where('company_wallet_id', $walletId))
            ->whereYear('transaction_date', $year)
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('counterparty', 'like', "%{$search}%")
                    ->orWhere('receipt_no', 'like', "%{$search}%");
            }))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('finance.wallet.index', compact(
            'year', 'walletList', 'selectedWallet', 'summary', 'totalBalance', 'transactions'
        ));
    }

    public function storeWallet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holder_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'currency' => 'required|string|size:3',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);

        $opening = (float) ($validated['opening_balance'] ?? 0);

        $wallet = CompanyWallet::create([
            'name' => $validated['name'],
            'holder_name' => $validated['holder_name'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'currency' => $validated['currency'],
            'opening_balance' => $opening,
            'current_balance' => 0,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($opening > 0) {
            $this->wallets->recordTransaction(
                $wallet,
                'deposit',
                $opening,
                __('finance.wallet_opening_balance'),
                now()->toDateString(),
                null,
                null,
                __('finance.wallet_opening_note'),
            );
        }

        return redirect()
            ->route('finance.wallet', ['wallet' => $wallet->uuid])
            ->with('success', __('messages.created'));
    }

    public function storeTransaction(Request $request)
    {
        $validated = $this->validateTransaction($request);

        $wallet = CompanyWallet::findOrFail($validated['company_wallet_id']);

        $this->wallets->recordTransaction(
            $wallet,
            $validated['type'],
            (float) $validated['amount'],
            $validated['description'],
            $validated['transaction_date'],
            $validated['counterparty'] ?? null,
            $validated['receipt_no'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with('success', __('messages.created'));
    }

    public function updateTransaction(Request $request, WalletTransaction $transaction)
    {
        $validated = $this->validateTransaction($request, $transaction);

        DB::transaction(function () use ($transaction, $validated) {
            $this->wallets->reverseTransaction($transaction);

            $wallet = CompanyWallet::findOrFail($validated['company_wallet_id']);

            $this->wallets->recordTransaction(
                $wallet,
                $validated['type'],
                (float) $validated['amount'],
                $validated['description'],
                $validated['transaction_date'],
                $validated['counterparty'] ?? null,
                $validated['receipt_no'] ?? null,
                $validated['notes'] ?? null,
                $transaction->user_id,
            );
        });

        return back()->with('success', __('messages.saved'));
    }

    public function destroyTransaction(WalletTransaction $transaction)
    {
        $this->wallets->reverseTransaction($transaction);

        return back()->with('success', __('messages.deleted'));
    }

    protected function validateTransaction(Request $request, ?WalletTransaction $transaction = null): array
    {
        return $request->validate([
            'company_wallet_id' => 'required|exists:company_wallets,id',
            'type' => 'required|in:deposit,expense',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'counterparty' => 'nullable|string|max:255',
            'receipt_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    protected function resolveWallet(Request $request, $walletList): ?CompanyWallet
    {
        if ($request->filled('wallet')) {
            $wallet = CompanyWallet::where('uuid', $request->wallet)->first();

            if ($wallet) {
                return $wallet;
            }
        }

        return $walletList->first();
    }
}
