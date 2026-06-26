<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerProfileService;
use Tests\FeatureTestCase;

class CustomerProfileTest extends FeatureTestCase
{
    public function test_customer_show_lists_orders_and_summary(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'ABC Tekstil',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        Order::create([
            'order_number' => 'ORD-CUST-001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'purchase_total' => 3000,
            'sale_total' => 5000,
            'margin_total' => 2000,
            'total_amount' => 5000,
            'amount_collected' => 2000,
        ]);

        $summary = app(CustomerProfileService::class)->summary($customer);

        $this->assertSame(1, $summary['order_count']);
        $this->assertEquals(5000.0, $summary['sale_total']);
        $this->assertEquals(3000.0, $summary['remaining_receivable']);

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('ABC Tekstil')
            ->assertSee('ORD-CUST-001')
            ->assertSee(__('customers.remaining_receivable'));
    }

    public function test_order_accepts_custom_order_number(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Test Müşteri',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $response = $this->post(route('orders.store'), [
            'order_number' => 'SIP-2026-9999',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'currency' => 'USD',
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => 'Kumaş', 'quantity' => 5, 'sale_unit_price' => 100],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['order_number' => 'SIP-2026-9999']);
    }

    public function test_order_number_must_be_unique(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Test Müşteri',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        Order::create([
            'order_number' => 'DUP-001',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'order_date' => now(),
            'currency' => 'USD',
            'total_amount' => 0,
        ]);

        $response = $this->post(route('orders.store'), [
            'order_number' => 'DUP-001',
            'customer_id' => $customer->id,
            'status' => 'draft',
            'currency' => 'USD',
            'order_date' => now()->toDateString(),
            'items' => [],
        ]);

        $response->assertSessionHasErrors('order_number');
    }
}
