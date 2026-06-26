<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\OrderFinanceService;
use App\Services\SupplierProfileService;
use Tests\FeatureTestCase;

class SupplierProfileTest extends FeatureTestCase
{
    public function test_supplier_show_lists_linked_orders_and_products(): void
    {
        $this->actingAsAdmin();

        $supplier = Supplier::create([
            'company_name' => 'Test Tedarik A.Ş.',
            'type' => 'manufacturer',
            'status' => 'active',
            'currency' => 'USD',
            'created_by' => auth()->id(),
        ]);

        $customer = Customer::create([
            'company_name' => 'Test Müşteri',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'purchase_total' => 5000,
            'sale_total' => 7000,
            'margin_total' => 2000,
            'total_amount' => 7000,
        ]);

        $summary = app(SupplierProfileService::class)->summary($supplier);

        $this->assertSame(1, $summary['order_count']);
        $this->assertEquals(5000.0, $summary['purchase_total']);

        $this->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Test Tedarik A.Ş.')
            ->assertSee('ORD-TEST-001');
    }

    public function test_order_requires_supplier_when_purchase_price_entered(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('orders.store'), [
            'status' => 'draft',
            'currency' => 'USD',
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Pamuk', 'quantity' => 10, 'purchase_unit_price' => 100, 'sale_unit_price' => 150],
            ],
        ]);

        $response->assertSessionHasErrors('supplier_id');
    }

    public function test_supplier_show_finds_orders_linked_via_payment(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Müşteri X',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $supplier = Supplier::create([
            'company_name' => 'Tedarikçi Y',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-LINK-001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'purchase_total' => 3000,
            'sale_total' => 4500,
            'margin_total' => 1500,
            'total_amount' => 4500,
        ]);

        $account = app(OrderFinanceService::class)->ensureSupplierAccount($supplier);

        Payment::create([
            'payment_number' => 'PAY-001',
            'account_id' => $account->id,
            'supplier_id' => $supplier->id,
            'order_id' => $order->id,
            'amount' => 1000,
            'currency' => 'USD',
            'payment_date' => now(),
        ]);

        $summary = app(SupplierProfileService::class)->summary($supplier);

        $this->assertSame(1, $summary['order_count']);

        $this->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('ORD-LINK-001');
    }
}
