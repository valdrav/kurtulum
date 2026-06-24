<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\OrderFinanceService;
use Tests\FeatureTestCase;

class OrderFinanceTest extends FeatureTestCase
{
    protected function createConfirmedOrder(float $sale = 10000, float $purchase = 7000): Order
    {
        $customer = Customer::create([
            'company_name' => 'Test Müşteri A.Ş.',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $supplier = Supplier::create([
            'company_name' => 'Test Tedarikçi Ltd.',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-0001',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'TRY',
            'sale_total' => $sale,
            'purchase_total' => $purchase,
            'margin_total' => $sale - $purchase,
            'total_amount' => $sale,
        ]);

        app(OrderFinanceService::class)->postOrderLedger($order->fresh());

        return $order->fresh();
    }

    public function test_confirming_order_posts_customer_receivable_and_supplier_payable(): void
    {
        $order = $this->createConfirmedOrder();

        $customerAccount = Account::where('customer_id', $order->customer_id)->first();
        $supplierAccount = Account::where('supplier_id', $order->supplier_id)->first();

        $this->assertNotNull($order->finance_posted_at);
        $this->assertEquals(10000, (float) $customerAccount->current_balance);
        $this->assertEquals(7000, (float) $supplierAccount->current_balance);
    }

    public function test_collection_reduces_receivable_and_increases_treasury(): void
    {
        $this->actingAsAdmin();
        company_treasury()->ensureDefaults();

        $order = $this->createConfirmedOrder();
        $customerAccount = Account::where('customer_id', $order->customer_id)->first();
        $treasury = company_treasury()->defaultAccount();
        $treasuryBefore = (float) $treasury->current_balance;
        $method = PaymentMethod::where('code', 'cash')->first();

        $response = $this->post(route('finance.collections.store'), [
            'order_id' => $order->id,
            'account_id' => $customerAccount->id,
            'treasury_account_id' => $treasury->id,
            'payment_method_id' => $method->id,
            'amount' => 10000,
            'currency' => 'TRY',
            'collection_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('orders.show', $order));

        $customerAccount->refresh();
        $treasury->refresh();
        $order->refresh();

        $this->assertEquals(0, (float) $customerAccount->current_balance);
        $this->assertEquals($treasuryBefore + 10000, (float) $treasury->current_balance);
        $this->assertEquals(10000, (float) $order->amount_collected);
    }

    public function test_payment_reduces_payable_and_decreases_treasury(): void
    {
        $this->actingAsAdmin();
        company_treasury()->ensureDefaults();

        $order = $this->createConfirmedOrder();
        $supplierAccount = Account::where('supplier_id', $order->supplier_id)->first();
        $treasury = company_treasury()->defaultAccount();
        $treasuryBefore = (float) $treasury->current_balance;
        $method = PaymentMethod::where('code', 'cash')->first();

        $response = $this->post(route('finance.payments.store'), [
            'order_id' => $order->id,
            'account_id' => $supplierAccount->id,
            'treasury_account_id' => $treasury->id,
            'payment_method_id' => $method->id,
            'amount' => 7000,
            'currency' => 'TRY',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('orders.show', $order));

        $supplierAccount->refresh();
        $treasury->refresh();
        $order->refresh();

        $this->assertEquals(0, (float) $supplierAccount->current_balance);
        $this->assertEquals($treasuryBefore - 7000, (float) $treasury->current_balance);
        $this->assertEquals(7000, (float) $order->amount_paid);
    }

    public function test_full_settlement_leaves_margin_in_treasury(): void
    {
        $this->actingAsAdmin();
        company_treasury()->ensureDefaults();

        $order = $this->createConfirmedOrder(10000, 7000);
        $customerAccount = Account::where('customer_id', $order->customer_id)->first();
        $supplierAccount = Account::where('supplier_id', $order->supplier_id)->first();
        $treasury = company_treasury()->defaultAccount();
        $treasuryBefore = (float) $treasury->current_balance;
        $method = PaymentMethod::where('code', 'cash')->first();

        $this->post(route('finance.collections.store'), [
            'order_id' => $order->id,
            'account_id' => $customerAccount->id,
            'treasury_account_id' => $treasury->id,
            'payment_method_id' => $method->id,
            'amount' => 10000,
            'currency' => 'TRY',
            'collection_date' => now()->toDateString(),
        ]);

        $this->post(route('finance.payments.store'), [
            'order_id' => $order->id,
            'account_id' => $supplierAccount->id,
            'treasury_account_id' => $treasury->id,
            'payment_method_id' => $method->id,
            'amount' => 7000,
            'currency' => 'TRY',
            'payment_date' => now()->toDateString(),
        ]);

        $treasury->refresh();
        $order->refresh();

        $this->assertEquals($treasuryBefore + 3000, (float) $treasury->current_balance);
        $this->assertEquals('settled', app(OrderFinanceService::class)->financeSummary($order)['finance_status']);
    }

    public function test_order_show_displays_finance_panel(): void
    {
        $this->actingAsAdmin();
        $order = $this->createConfirmedOrder();

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee(__('orders.finance_title'));
        $response->assertSee(__('orders.record_collection'));
    }
}
