<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ExternalApiConnection;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Models\User;
use App\Services\ExternalApiConnectionService;
use Tests\FeatureTestCase;

class ExternalApiTest extends FeatureTestCase
{
    protected function createConnection(Customer $customer, array $overrides = []): string
    {
        $admin = $this->actingAsAdmin();

        $result = app(ExternalApiConnectionService::class)->create(array_merge([
            'name' => 'Test API',
            'customer_id' => $customer->id,
            'is_active' => true,
            'view_customer' => true,
            'view_orders' => true,
            'view_shipments' => true,
            'view_shipment_costs' => true,
            'edit_shipment_costs' => true,
        ], $overrides), $admin->id);

        return $result['plain_token'];
    }

    public function test_orders_and_shipments_are_scoped_to_linked_customer(): void
    {
        Setting::set('external_api_enabled', '1', 'integrations');

        $customerA = Customer::create([
            'company_name' => 'Müşteri A',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $customerB = Customer::create([
            'company_name' => 'Müşteri B',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $orderA = Order::create([
            'order_number' => 'ORD-A-001',
            'customer_id' => $customerA->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'total_amount' => 1000,
        ]);

        Order::create([
            'order_number' => 'ORD-B-001',
            'customer_id' => $customerB->id,
            'status' => 'confirmed',
            'order_date' => now(),
            'currency' => 'USD',
            'total_amount' => 2000,
        ]);

        $shipmentA = Shipment::create([
            'shipment_number' => 'SHP-A-001',
            'order_id' => $orderA->id,
            'transport_mode' => 'road',
            'status' => 'in_transit',
            'currency' => 'USD',
            'total_cost' => 500,
        ]);

        ShipmentCost::create([
            'shipment_id' => $shipmentA->id,
            'item_name' => 'Nakliye',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        Shipment::create([
            'shipment_number' => 'SHP-B-001',
            'customer_id' => $customerB->id,
            'transport_mode' => 'sea',
            'status' => 'draft',
            'currency' => 'USD',
        ]);

        $token = $this->createConnection($customerA);

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/me', $headers)
            ->assertOk()
            ->assertJsonPath('customer.company_name', 'Müşteri A')
            ->assertJsonPath('permissions.orders', true);

        $orders = $this->getJson('/api/orders', $headers)
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $orders);
        $this->assertSame('ORD-A-001', $orders[0]['order_number']);

        $shipments = $this->getJson('/api/shipments', $headers)
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $shipments);
        $this->assertSame('SHP-A-001', $shipments[0]['shipment_number']);

        $detail = $this->getJson('/api/shipments/'.$shipmentA->uuid, $headers)
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $detail['costs']);
        $this->assertSame('Nakliye', $detail['costs'][0]['item_name']);

        $this->getJson('/api/orders/'.$orderA->uuid, $headers)->assertOk();
        $this->getJson('/api/orders/'.Order::where('customer_id', $customerB->id)->value('uuid'), $headers)
            ->assertNotFound();
    }

    public function test_orders_endpoint_returns_forbidden_without_permission(): void
    {
        Setting::set('external_api_enabled', '1', 'integrations');

        $customer = Customer::create([
            'company_name' => 'Müşteri C',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $token = $this->createConnection($customer, [
            'view_orders' => false,
            'view_shipments' => false,
        ]);

        $this->getJson('/api/orders', ['Authorization' => 'Bearer '.$token])
            ->assertForbidden();
    }
}
