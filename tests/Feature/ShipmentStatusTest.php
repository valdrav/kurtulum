<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Shipment;
use Tests\FeatureTestCase;

class ShipmentStatusTest extends FeatureTestCase
{
    public function test_manager_can_set_awaiting_transit_with_location(): void
    {
        $this->actingAsAdmin();

        $customer = Customer::create([
            'company_name' => 'Status Test Co',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-TEST-001',
            'customer_id' => $customer->id,
            'transport_mode' => 'sea',
            'status' => 'in_transit',
            'currency' => 'USD',
        ]);

        $response = $this->post(route('shipments.status', $shipment), [
            'status' => 'awaiting_transit',
            'status_location' => 'Cidde',
            'manual' => 1,
            'note' => 'Karşı kıyıya geçiş için demirde',
        ]);

        $response->assertRedirect();
        $shipment->refresh();

        $this->assertSame('awaiting_transit', $shipment->status);
        $this->assertSame('Cidde', $shipment->status_location);
        $this->assertSame('Karşıya geçiş bekleniyor · Cidde', $shipment->statusDisplay());
        $this->assertNotNull($shipment->status_updated_at);
    }

    public function test_sea_preset_port_waiting_jeddah(): void
    {
        $this->actingAsAdmin();

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-TEST-002',
            'transport_mode' => 'sea',
            'status' => 'booked',
            'currency' => 'USD',
        ]);

        $this->post(route('shipments.status', $shipment), [
            'status' => 'port_waiting',
            'status_location' => 'Cidde',
            'manual' => 1,
        ])->assertRedirect();

        $shipment->refresh();
        $this->assertSame('port_waiting', $shipment->status);
        $this->assertStringContainsString('Cidde', $shipment->statusDisplay());
    }
}
