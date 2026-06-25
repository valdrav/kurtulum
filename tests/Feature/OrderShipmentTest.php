<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderShipmentService;
use Tests\FeatureTestCase;

class OrderShipmentTest extends FeatureTestCase
{
    public function test_confirmed_order_automatically_gets_shipment(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Shipment Test Customer',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $response = $this->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'USD',
            'incoterm' => 'FCA',
            'order_date' => now()->toDateString(),
            'items' => [
                ['description' => '480 box', 'quantity' => 1, 'sale_unit_price' => 1000, 'purchase_unit_price' => 800],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_sync_orders_without_shipments_backfills_records(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Backfill Customer',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-BF-001',
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'currency' => 'USD',
            'order_date' => now(),
        ]);

        $this->assertSame(0, Shipment::where('order_id', $order->id)->count());

        $count = app(OrderShipmentService::class)->syncOrdersWithoutShipments();

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(1, Shipment::where('order_id', $order->id)->count());
    }
}
