<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class OrderFinanceService
{
    public function __construct(
        protected AccountLedgerService $ledger,
        protected ExchangeRateService $rates,
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
                $customerAmount = $this->rates->amountForAccount(
                    $saleTotal,
                    $order->currency ?? 'USD',
                    $customerAccount
                );
                $this->ledger->adjustBalance(
                    $customerAccount,
                    $customerAmount,
                    $order,
                    'Sipariş alacağı: ' . $order->order_number,
                    $order->order_date?->toDateString()
                );
            }

            if ($order->supplier && $purchaseTotal > 0) {
                $supplierAccount = $this->ensureSupplierAccount($order->supplier);
                $supplierAmount = $this->rates->amountForAccount(
                    $purchaseTotal,
                    $order->currency ?? 'USD',
                    $supplierAccount
                );
                $this->ledger->adjustBalance(
                    $supplierAccount,
                    $supplierAmount,
                    $order,
                    'Sipariş borcu: ' . $order->order_number,
                    $order->order_date?->toDateString()
                );
            }

            $order->update(['finance_posted_at' => now()]);
        });
    }

    /** Onaylı sipariş düzenlendiğinde cari kayıtları günceller. */
    public function resyncOrderLedger(Order $order): void
    {
        if (! $order->finance_posted_at) {
            if ($order->status === 'confirmed') {
                $this->postOrderLedger($order);
            }

            return;
        }

        $order->loadMissing(['customer', 'supplier']);

        DB::transaction(function () use ($order) {
            $this->reverseOrderLedgerPostings($order);

            if ($order->status === 'cancelled') {
                $order->update(['finance_posted_at' => null]);

                return;
            }

            $order->update(['finance_posted_at' => null]);
            $this->postOrderLedger($order->fresh());
        });
    }

    protected function reverseOrderLedgerPostings(Order $order): void
    {
        \App\Models\AccountTransaction::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where(function ($q) {
                $q->where('description', 'like', 'Sipariş alacağı:%')
                    ->orWhere('description', 'like', 'Sipariş borcu:%');
            })
            ->with('account')
            ->get()
            ->each(function ($tx) use ($order) {
                if (! $tx->account) {
                    return;
                }

                $delta = $tx->type === 'credit'
                    ? -(float) $tx->amount
                    : (float) $tx->amount;

                $this->ledger->adjustBalance(
                    $tx->account,
                    $delta,
                    $order,
                    'Düzeltme (eski): ' . $tx->description,
                    now()->toDateString()
                );
            });
    }

    public function recordCollection(array $validated, PaymentMethod $method, float $fee, ?Order $order = null): Collection
    {
        return DB::transaction(function () use ($validated, $method, $fee, $order) {
            $cariAccount = Account::query()->cari()->findOrFail($validated['account_id']);
            $treasury = $this->resolveTreasury($validated['treasury_account_id'] ?? null);
            $gross = (float) $validated['amount'];
            $net = round($gross - $fee, 2);
            $date = $validated['collection_date'];
            $currency = $validated['currency'];
            $exchangeRate = $this->resolveTransactionRate(
                $currency,
                isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null
            );

            $collection = Collection::create([
                'collection_number' => $this->nextNumber('COL'),
                'account_id' => $cariAccount->id,
                'customer_id' => $cariAccount->customer_id ?? $order?->customer_id,
                'order_id' => $order?->id,
                'treasury_account_id' => $treasury->id,
                'amount' => $gross,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'collection_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'collection_date' => $date,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $this->applyCollectionLedger($collection->fresh(['account', 'treasuryAccount']), $order);

            return $collection;
        });
    }

    public function updateCollection(Collection $collection, array $validated, PaymentMethod $method, float $fee): Collection
    {
        return DB::transaction(function () use ($collection, $validated, $method, $fee) {
            $collection->load(['account', 'treasuryAccount', 'order']);
            $oldNet = round((float) $collection->amount - (float) $collection->fee_amount, 2);

            if ($collection->order) {
                $collection->order->decrement(
                    'amount_collected',
                    min($oldNet, (float) $collection->order->amount_collected)
                );
            }

            $this->reverseCollectionLedger($collection, 'Düzeltme iptali');

            $currency = $validated['currency'];
            $exchangeRate = $this->resolveTransactionRate(
                $currency,
                isset($validated['exchange_rate']) && (float) $validated['exchange_rate'] > 0
                    ? (float) $validated['exchange_rate']
                    : (float) $collection->exchange_rate
            );

            $collection->update([
                'account_id' => $validated['account_id'],
                'customer_id' => Account::find($validated['account_id'])?->customer_id ?? $collection->customer_id,
                'treasury_account_id' => $validated['treasury_account_id'] ?? $collection->treasury_account_id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'collection_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'collection_date' => $validated['collection_date'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? $validated['notes'] ?? null,
            ]);

            $collection->refresh()->load(['account', 'treasuryAccount', 'order']);
            $this->applyCollectionLedger($collection, $collection->order);

            return $collection;
        });
    }

    public function recordPayment(array $validated, PaymentMethod $method, float $fee, ?Order $order = null): Payment
    {
        return DB::transaction(function () use ($validated, $method, $fee, $order) {
            $cariAccount = Account::query()->cari()->findOrFail($validated['account_id']);
            $treasury = $this->resolveTreasury($validated['treasury_account_id'] ?? null);
            $gross = (float) $validated['amount'];
            $total = round($gross + $fee, 2);
            $date = $validated['payment_date'];
            $currency = $validated['currency'];
            $exchangeRate = $this->resolveTransactionRate(
                $currency,
                isset($validated['exchange_rate']) ? (float) $validated['exchange_rate'] : null
            );

            $payment = Payment::create([
                'payment_number' => $this->nextNumber('PAY'),
                'account_id' => $cariAccount->id,
                'supplier_id' => $cariAccount->supplier_id ?? $order?->supplier_id,
                'order_id' => $order?->id,
                'treasury_account_id' => $treasury->id,
                'amount' => $gross,
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'payment_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'payment_date' => $date,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $this->applyPaymentLedger($payment->fresh(['account', 'treasuryAccount']), $order);

            return $payment;
        });
    }

    public function updatePayment(Payment $payment, array $validated, PaymentMethod $method, float $fee): Payment
    {
        return DB::transaction(function () use ($payment, $validated, $method, $fee) {
            $payment->load(['account', 'treasuryAccount', 'order']);

            if ($payment->order) {
                $payment->order->decrement(
                    'amount_paid',
                    min((float) $payment->amount, (float) $payment->order->amount_paid)
                );
            }

            $this->reversePaymentLedger($payment, 'Düzeltme iptali');

            $currency = $validated['currency'];
            $exchangeRate = $this->resolveTransactionRate(
                $currency,
                isset($validated['exchange_rate']) && (float) $validated['exchange_rate'] > 0
                    ? (float) $validated['exchange_rate']
                    : (float) $payment->exchange_rate
            );

            $payment->update([
                'account_id' => $validated['account_id'],
                'supplier_id' => Account::find($validated['account_id'])?->supplier_id ?? $payment->supplier_id,
                'treasury_account_id' => $validated['treasury_account_id'] ?? $payment->treasury_account_id,
                'amount' => $validated['amount'],
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'fee_amount' => $fee,
                'payment_method_id' => $method->id,
                'payment_method' => $method->code,
                'method_data' => $validated['method_data'] ?? null,
                'payment_date' => $validated['payment_date'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['description'] ?? $validated['notes'] ?? null,
            ]);

            $payment->refresh()->load(['account', 'treasuryAccount', 'order']);
            $this->applyPaymentLedger($payment, $payment->order);

            return $payment;
        });
    }

    public function reverseCollection(Collection $collection): void
    {
        DB::transaction(function () use ($collection) {
            $net = round((float) $collection->amount - (float) $collection->fee_amount, 2);

            if ($collection->order_id) {
                $order = Order::find($collection->order_id);
                if ($order) {
                    $order->decrement('amount_collected', min($net, (float) $order->amount_collected));
                }
            }

            $this->reverseCollectionLedger($collection, 'İptal');
            $collection->delete();
        });
    }

    public function reversePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            if ($payment->order_id) {
                $order = Order::find($payment->order_id);
                if ($order) {
                    $order->decrement('amount_paid', min((float) $payment->amount, (float) $order->amount_paid));
                }
            }

            $this->reversePaymentLedger($payment, 'İptal');
            $payment->delete();
        });
    }

    /** Sipariş silinirken bağlı finans kayıtlarını geri alır. */
    public function deleteOrder(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            $summary = [
                'collections' => 0,
                'payments' => 0,
                'income_expenses' => 0,
                'shipments' => 0,
                'finance_reversed' => false,
            ];

            $order->load(['collections', 'payments', 'shipments']);

            foreach ($order->collections as $collection) {
                $this->reverseCollection($collection);
                $summary['collections']++;
            }

            foreach ($order->payments as $payment) {
                $this->reversePayment($payment);
                $summary['payments']++;
            }

            IncomeExpense::query()
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->get()
                ->each(function (IncomeExpense $entry) use (&$summary) {
                    $this->reverseIncomeExpense($entry);
                    $entry->delete();
                    $summary['income_expenses']++;
                });

            if ($order->finance_posted_at) {
                $this->reverseOrderLedgerPostings($order);
                $order->update(['finance_posted_at' => null]);
                $summary['finance_reversed'] = true;
            }

            $shipmentDeletion = app(ShipmentDeletionService::class);
            foreach ($order->shipments as $shipment) {
                $shipmentDeletion->deleteShipment($shipment);
                $summary['shipments']++;
            }

            $order->items()->delete();
            $order->delete();

            return $summary;
        });
    }

    protected function reverseIncomeExpense(IncomeExpense $entry): void
    {
        if (! $entry->account_id) {
            return;
        }

        $account = Account::find($entry->account_id);

        if (! $account?->is_treasury) {
            return;
        }

        $amount = $this->rates->amountForAccount(
            (float) $entry->amount,
            $entry->currency,
            $account,
            (float) $entry->exchange_rate
        );
        $delta = $entry->type === 'income' ? -$amount : $amount;

        $this->ledger->adjustBalance(
            $account,
            $delta,
            $entry,
            'İptal (sipariş silindi): ' . $entry->displayTitle(),
            now()->toDateString()
        );
    }

    protected function applyCollectionLedger(Collection $collection, ?Order $order = null): void
    {
        $cari = $collection->account;
        $treasury = $collection->treasuryAccount ?? company_treasury()->defaultAccount();
        $net = round((float) $collection->amount - (float) $collection->fee_amount, 2);
        $date = $collection->collection_date?->toDateString() ?? now()->toDateString();
        $label = 'Tahsilat: ' . $collection->collection_number . ($order ? ' (' . $order->order_number . ')' : '');

        $cariAmount = $cari
            ? $this->rates->amountForAccount($net, $collection->currency, $cari, (float) $collection->exchange_rate)
            : 0;
        $treasuryAmount = $this->rates->amountForAccount($net, $collection->currency, $treasury, (float) $collection->exchange_rate);
        $lockedRate = (float) $collection->exchange_rate;

        if ($cari) {
            $this->ledger->adjustBalance($cari, -$cariAmount, $collection, $label, $date, null, $lockedRate);
        }
        $this->ledger->adjustBalance($treasury, $treasuryAmount, $collection, $label, $date, null, $lockedRate);

        if ($order) {
            $this->incrementOrderCollected($order, $net);
        }
    }

    protected function reverseCollectionLedger(Collection $collection, string $prefix = 'İptal'): void
    {
        $cari = $collection->account;
        $treasury = $collection->treasuryAccount ?? company_treasury()->defaultAccount();
        $net = round((float) $collection->amount - (float) $collection->fee_amount, 2);
        $date = now()->toDateString();
        $label = $prefix . ' tahsilat: ' . $collection->collection_number;

        $cariAmount = $cari
            ? $this->rates->amountForAccount($net, $collection->currency, $cari, (float) $collection->exchange_rate)
            : 0;
        $treasuryAmount = $this->rates->amountForAccount($net, $collection->currency, $treasury, (float) $collection->exchange_rate);
        $lockedRate = (float) $collection->exchange_rate;

        if ($cari) {
            $this->ledger->adjustBalance($cari, $cariAmount, $collection, $label, $date, null, $lockedRate);
        }
        $this->ledger->adjustBalance($treasury, -$treasuryAmount, $collection, $label, $date, null, $lockedRate);
    }

    protected function applyPaymentLedger(Payment $payment, ?Order $order = null): void
    {
        $cari = $payment->account;
        $treasury = $payment->treasuryAccount ?? company_treasury()->defaultAccount();
        $total = round((float) $payment->amount + (float) $payment->fee_amount, 2);
        $date = $payment->payment_date?->toDateString() ?? now()->toDateString();
        $label = 'Ödeme: ' . $payment->payment_number . ($order ? ' (' . $order->order_number . ')' : '');

        $cariAmount = $cari
            ? $this->rates->amountForAccount($total, $payment->currency, $cari, (float) $payment->exchange_rate)
            : 0;
        $treasuryAmount = $this->rates->amountForAccount($total, $payment->currency, $treasury, (float) $payment->exchange_rate);
        $lockedRate = (float) $payment->exchange_rate;

        if ($cari) {
            $this->ledger->adjustBalance($cari, -$cariAmount, $payment, $label, $date, null, $lockedRate);
        }
        $this->ledger->adjustBalance($treasury, -$treasuryAmount, $payment, $label, $date, null, $lockedRate);

        if ($order) {
            $this->incrementOrderPaid($order, (float) $payment->amount);
        }
    }

    protected function reversePaymentLedger(Payment $payment, string $prefix = 'İptal'): void
    {
        $cari = $payment->account;
        $treasury = $payment->treasuryAccount ?? company_treasury()->defaultAccount();
        $total = round((float) $payment->amount + (float) $payment->fee_amount, 2);
        $date = now()->toDateString();
        $label = $prefix . ' ödeme: ' . $payment->payment_number;

        $cariAmount = $cari
            ? $this->rates->amountForAccount($total, $payment->currency, $cari, (float) $payment->exchange_rate)
            : 0;
        $treasuryAmount = $this->rates->amountForAccount($total, $payment->currency, $treasury, (float) $payment->exchange_rate);
        $lockedRate = (float) $payment->exchange_rate;

        if ($cari) {
            $this->ledger->adjustBalance($cari, $cariAmount, $payment, $label, $date, null, $lockedRate);
        }
        $this->ledger->adjustBalance($treasury, $treasuryAmount, $payment, $label, $date, null, $lockedRate);
    }

    public function financeSummary(Order $order): array
    {
        $sale = (float) ($order->sale_total ?? 0);
        $purchase = (float) ($order->purchase_total ?? 0);
        $collected = (float) ($order->amount_collected ?? 0);
        $paid = (float) ($order->amount_paid ?? 0);
        $margin = (float) ($order->margin_total ?? 0);
        $expenses = app(TradeFinanceService::class)->orderRelatedExpenses($order);
        $orderExpenses = (float) $expenses['total'];

        return [
            'sale_total' => $sale,
            'purchase_total' => $purchase,
            'margin_total' => $margin,
            'amount_collected' => $collected,
            'amount_paid' => $paid,
            'remaining_receivable' => max(0, $sale - $collected),
            'remaining_payable' => max(0, $purchase - $paid),
            'order_expenses' => $orderExpenses,
            'order_expense_items' => $expenses['items'],
            'net_margin' => round($margin - $orderExpenses, 2),
            'treasury_profit' => round($collected - $paid - $orderExpenses, 2),
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

    protected function resolveTransactionRate(string $currency, ?float $provided = null): float
    {
        $default = strtoupper(registry()->defaultCurrency()?->code ?? 'TRY');
        $currency = strtoupper($currency);

        if ($currency === $default) {
            return 1.0;
        }

        if ($provided !== null && $provided > 0) {
            return round($provided, 6);
        }

        return $this->rates->rateToDefaultCurrency($currency);
    }
}
