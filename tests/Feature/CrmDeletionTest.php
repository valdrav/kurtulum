<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\OrderFinanceService;
use Tests\FeatureTestCase;

class CrmDeletionTest extends FeatureTestCase
{
    public function test_customer_can_be_deleted_without_orders(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Silinecek Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_customer_with_active_order_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Aktif Siparişli Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        Order::create([
            'order_number' => 'ORD-CRM-001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'TRY',
            'sale_total' => 1000,
            'purchase_total' => 0,
            'total_amount' => 1000,
        ]);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_customer_with_balance_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Bakiyeli Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        Account::create([
            'code' => 'CARI-TEST-001',
            'name' => $customer->company_name,
            'type' => 'customer',
            'customer_id' => $customer->id,
            'currency' => 'TRY',
            'opening_balance' => 0,
            'current_balance' => 500,
            'is_active' => true,
        ]);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_supplier_can_be_deleted_after_cancelled_orders_only(): void
    {
        $this->actingAsAdmin();

        $supplier = Supplier::create([
            'company_name' => 'Silinecek Tedarikçi',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $customer = Customer::create([
            'company_name' => 'Dummy Customer',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        Order::create([
            'order_number' => 'ORD-CRM-002',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'status' => 'cancelled',
            'order_date' => now(),
            'currency' => 'TRY',
            'sale_total' => 0,
            'purchase_total' => 1000,
            'total_amount' => 0,
        ]);

        $this->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_deleted_order_allows_customer_delete_when_balance_zero(): void
    {
        $this->actingAsAdmin();
        company_treasury()->ensureDefaults();

        $customer = Customer::create([
            'company_name' => 'Sipariş Silinmiş Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $supplier = Supplier::create([
            'company_name' => 'Dummy Supplier',
            'status' => 'active',
            'currency' => 'TRY',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CRM-003',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'TRY',
            'sale_total' => 5000,
            'purchase_total' => 3000,
            'total_amount' => 5000,
        ]);

        app(OrderFinanceService::class)->postOrderLedger($order->fresh());
        app(OrderFinanceService::class)->deleteOrder($order->fresh());

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');
    }
}
