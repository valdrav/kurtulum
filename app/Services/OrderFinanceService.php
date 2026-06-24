<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\SystemCurrency;
use Illuminate\Support\Facades\DB;

class OrderFinanceService
{
    public function __construct(
        protected AccountLedgerService $ledger,
    ) {}

    public function ensureCustomerAccount(Customer $customer): Account
    {
        return Account::firstOrCreate(
            ['customer_id' => $customer->id, 'type' => 'customer'],
            [
                'code' => 'CAR-C' . str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                'name' => $customer->company_name,
                'currency' => $customer->currency ?? 'TRY',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    public function ensureSupplierAccount(Supplier $supplier): Account
    {
        return Account::firstOrCreate(
            ['supplier_id' => $supplier->id, 'type' => 'supplier'],
            [
                'code' => 'CAR-S' . str_pad((string) $supplier->id, 4, '0', STR_PAD_LEFT),
                'name' => $supplier->company_name ?? $supplier->name,
                'currency' => $supplier->currency ?? 'TRY',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    /** Sipariş onaylandığında müşteri alacağı ve tedarikçi borcu açılır. */
    public function postOrderLedger(Order $order): void
    {
        if ($order->finance_posted_at) {
            return;
        }

        $order->loadMissing(['customer', 'supplier']);
        $saleTotal = (float) ($order->sale_total ?? 0);
        $purchaseTotal = (float) ($order->purchase_total ?? 0);

        if ($saleTotal <= 0 && $purchaseTotal <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $saleTotal, $purchaseTotal) {
            if ($order->customer && $saleTotal > 0) {
                $customerAccount = $this->ensureCustomerAccount($order->customer);
                $this->ledger->adjustBalance(
                    $customerAccount,
                    $saleTotal,
                    $order,
                    'Sipariş alacağı: ' . $order->order_number,
                    $order->order_date?->toDateString()
                );
            }

            if ($order->supplier && $purchaseTotal > 0) {
                $supplierAccount = $this->ensureSupplierAccount($order->supplier);
                $this->ledger->adjustBalance(
                    $supplierAccount,
                    $purchaseTotal,
                    $order,
                    'Sipariş borcu: ' . $order->order_number,
                    $order->order_date?->toDateString()
                );
            }

            $order->update(['finance_posted_at' => now()]);
        });
    }

    public function recordCollection(array $validated, PaymentMethod $method, float $fee, ?Order $order = null): Collection
    {
        return DB::transaction(function () use ($validated, $method, $fee, $order) {
            $currency = SystemCurrency::where('code', $validated['currency'])->first();
            $cariAccount = Account::query()->cari()->findOrFail($validated['account_id']);
            $treasury = $this->resolveTreasury($validated['treasury_account_id'] ?? null);
            $gross = (float) $validated['amount'];
            $net = round($gross - $fee, 2);
            $date = $validated['collection_date'];

            $collection = Collection::create([
                'collection_number' => $this->nextNumber('COL'),
                'account_id' => $cariAccount->id,
                'customer_id' => $cariAccount->customer_id ?? $order?->customer_id,
                'order_id' => $order?->id,
                'treasury_account_id' => $treasury->id,
                'amount' => $gross,
                'currency' => $validated['currency'],
                'exchange_rate' => $currency?->tcmb_rate ?? $currency?->exchange_rate ?? 1,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'collection_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'collection_date' => $date,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $label = 'Tahsilat: ' . $collection->collection_number . ($order ? ' (' . $order->order_number . ')' : '');

            $this->ledger->adjustBalance($cariAccount, -$net, $collection, $label, $date);
            $this->ledger->adjustBalance($treasury, $net, $collection, $label, $date);

            if ($order) {
                $this->incrementOrderCollected($order, $net);
            }

            return $collection;
        });
    }

    public function recordPayment(array $validated, PaymentMethod $method, float $fee, ?Order $order = null): Payment
    {
        return DB::transaction(function () use ($validated, $method, $fee, $order) {
            $currency = SystemCurrency::where('code', $validated['currency'])->first();
            $cariAccount = Account::query()->cari()->findOrFail($validated['account_id']);
            $treasury = $this->resolveTreasury($validated['treasury_account_id'] ?? null);
            $gross = (float) $validated['amount'];
            $total = round($gross + $fee, 2);
            $date = $validated['payment_date'];

            $payment = Payment::create([
                'payment_number' => $this->nextNumber('PAY'),
                'account_id' => $cariAccount->id,
                'supplier_id' => $cariAccount->supplier_id ?? $order?->supplier_id,
                'order_id' => $order?->id,
                'treasury_account_id' => $treasury->id,
                'amount' => $gross,
                'currency' => $validated['currency'],
                'exchange_rate' => $currency?->tcmb_rate ?? $currency?->exchange_rate ?? 1,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'payment_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'payment_date' => $date,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $label = 'Ödeme: ' . $payment->payment_number . ($order ? ' (' . $order->order_number . ')' : '');

            $this->ledger->adjustBalance($cariAccount, -$total, $payment, $label, $date);
            $this->ledger->adjustBalance($treasury, -$total, $payment, $label, $date);

            if ($order) {
                $this->incrementOrderPaid($order, $gross);
            }

            return $payment;
        });
    }

    public function reverseCollection(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            $net = round((float) $collection->amount - (float) $collection->fee_amount, 2);
            $cari = $collection->account;
            $treasury = $collection->treasuryAccount ?? company_treasury()->defaultAccount();
            $date = now()->toDateString();

            if ($cari) {
                $this->ledger->adjustBalance($cari, $net, $collection, 'İptal tahsilat: ' . $collection->collection_number, $date);
            }
            $this->ledger->adjustBalance($treasury, -$net, $collection, 'İptal tahsilat: ' . $collection->collection_number, $date);

            if ($collection->order_id) {
                $order = Order::find($collection->order_id);
                if ($order) {
                    $order->decrement('amount_collected', min($net, (float) $order->amount_collected));
                }
            }

            $collection->delete();
        });
    }

    public function reversePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $total = round((float) $payment->amount + (float) $payment->fee_amount, 2);
            $cari = $payment->account;
            $treasury = $payment->treasuryAccount ?? company_treasury()->defaultAccount();
            $date = now()->toDateString();

            if ($cari) {
                $this->ledger->adjustBalance($cari, $total, $payment, 'İptal ödeme: ' . $payment->payment_number, $date);
            }
            $this->ledger->adjustBalance($treasury, $total, $payment, 'İptal ödeme: ' . $payment->payment_number, $date);

            if ($payment->order_id) {
                $order = Order::find($payment->order_id);
                if ($order) {
                    $order->decrement('amount_paid', min((float) $payment->amount, (float) $order->amount_paid));
                }
            }

            $payment->delete();
        });
    }

    public function financeSummary(Order $order): array
    {
        $sale = (float) ($order->sale_total ?? 0);
        $purchase = (float) ($order->purchase_total ?? 0);
        $collected = (float) ($order->amount_collected ?? 0);
        $paid = (float) ($order->amount_paid ?? 0);
        $margin = (float) ($order->margin_total ?? 0);

        return [
            'sale_total' => $sale,
            'purchase_total' => $purchase,
            'margin_total' => $margin,
            'amount_collected' => $collected,
            'amount_paid' => $paid,
            'remaining_receivable' => max(0, $sale - $collected),
            'remaining_payable' => max(0, $purchase - $paid),
            'treasury_profit' => $collected - $paid,
            'finance_status' => $this->financeStatus($sale, $purchase, $collected, $paid),
            'finance_posted' => (bool) $order->finance_posted_at,
        ];
    }

    protected function incrementOrderCollected(Order $order, float $amount): void
    {
        $order->increment('amount_collected', $amount);
    }

    protected function incrementOrderPaid(Order $order, float $amount): void
    {
        $order->increment('amount_paid', $amount);
    }

    protected function financeStatus(float $sale, float $purchase, float $collected, float $paid): string
    {
        $receivableDone = $sale <= 0 || $collected >= $sale - 0.01;
        $payableDone = $purchase <= 0 || $paid >= $purchase - 0.01;

        if ($receivableDone && $payableDone) {
            return 'settled';
        }

        if ($collected > 0 || $paid > 0) {
            return 'partial';
        }

        return 'open';
    }

    protected function resolveTreasury(?int $accountId): Account
    {
        if ($accountId) {
            $account = Account::query()->whereKey($accountId)->where('is_treasury', true)->first();
            if ($account) {
                return $account;
            }
        }

        return company_treasury()->defaultAccount();
    }

    protected function nextNumber(string $prefix): string
    {
        $model = $prefix === 'COL' ? Collection::class : Payment::class;
        $last = $model::withTrashed()->where('created_at', '>=', now()->startOfYear())->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $last);
    }
}
