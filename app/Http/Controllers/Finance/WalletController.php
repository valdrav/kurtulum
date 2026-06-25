<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CompanyWallet;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CompanyWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $walletUser = $this->resolveWalletUser($request);
        $year = (int) ($request->year ?: now()->year);
        $walletList = $this->wallets->wallets($walletUser->id);
        $selectedWallet = $this->resolveWallet($request, $walletList, $walletUser);
        $walletId = $selectedWallet?->id;
        $walletIds = $walletId ? [$walletId] : $this->wallets->walletIdsForUser($walletUser->id);

        $summary = $this->wallets->annualSummary($walletId, $year, $walletUser->id);
        $totalBalance = $walletId
            ? (float) $selectedWallet->current_balance
            : $this->wallets->totalBalance($walletUser->id);

        $transactions = WalletTransaction::with(['wallet.user', 'user'])
            ->whereIn('company_wallet_id', $walletIds)
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

        $selectableUsers = $this->canViewOtherWallets()
            ? User::query()->where('is_active', true)->orderBy('name')->get(['id', 'uuid', 'name'])
            : collect();

        return view('finance.wallet.index', compact(
            'year', 'walletList', 'selectedWallet', 'summary', 'totalBalance', 'transactions',
            'walletUser', 'selectableUsers'
        ));
    }

    public function storeWallet(Request $request)
    {
        $walletUser = $this->resolveWalletUser($request);

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
            'user_id' => $walletUser->id,
            'name' => $validated['name'],
            'holder_name' => $validated['holder_name'] ?? $walletUser->name,
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
            ->route('finance.wallet', ['wallet' => $wallet->uuid, 'user' => $walletUser->uuid])
            ->with('success', __('messages.created'));
    }

    public function storeTransaction(Request $request)
    {
        $walletUser = $this->resolveWalletUser($request);
        $validated = $this->validateTransaction($request, $walletUser);

        $wallet = CompanyWallet::where('user_id', $walletUser->id)->findOrFail($validated['company_wallet_id']);

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
        $this->authorizeTransaction($transaction);
        $walletUser = $transaction->wallet?->user ?? $this->resolveWalletUser($request);
        $validated = $this->validateTransaction($request, $walletUser, $transaction);

        DB::transaction(function () use ($transaction, $validated, $walletUser) {
            $this->wallets->reverseTransaction($transaction);

            $wallet = CompanyWallet::where('user_id', $walletUser->id)->findOrFail($validated['company_wallet_id']);

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
        $this->authorizeTransaction($transaction);
        $this->wallets->reverseTransaction($transaction);

        return back()->with('success', __('messages.deleted'));
    }

    protected function validateTransaction(Request $request, User $walletUser, ?WalletTransaction $transaction = null): array
    {
        return $request->validate([
            'company_wallet_id' => [
                'required',
                Rule::exists('company_wallets', 'id')->where(
                    fn ($q) => $q->where('user_id', $walletUser->id)->where('is_active', true)
                ),
            ],
            'type' => 'required|in:deposit,expense',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'counterparty' => 'nullable|string|max:255',
            'receipt_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    protected function resolveWalletUser(Request $request): User
    {
        if ($this->canViewOtherWallets() && $request->filled('user')) {
            $user = User::query()
                ->where('is_active', true)
                ->where(function ($q) use ($request) {
                    $q->where('uuid', $request->user)->orWhere('id', $request->user);
                })
                ->first();

            if ($user) {
                return $user;
            }
        }

        return auth()->user();
    }

    protected function resolveWallet(Request $request, $walletList, User $walletUser): ?CompanyWallet
    {
        if ($request->filled('wallet')) {
            $wallet = CompanyWallet::query()
                ->where('user_id', $walletUser->id)
                ->where('uuid', $request->wallet)
                ->first();

            if ($wallet) {
                return $wallet;
            }
        }

        return $walletList->first();
    }

    protected function authorizeTransaction(WalletTransaction $transaction): void
    {
        $ownerId = $transaction->wallet?->user_id;

        if (! $ownerId) {
            abort(404);
        }

        if ($ownerId === auth()->id()) {
            return;
        }

        if ($this->canViewOtherWallets()) {
            return;
        }

        abort(403);
    }

    protected function canViewOtherWallets(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return can_access('finance.edit') || can_access('users.view');
    }
}
