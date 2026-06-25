<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentCost;
use Tests\FeatureTestCase;

class ShipmentCostTest extends FeatureTestCase
{
    public function test_admin_can_view_shipment_costs_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shipments.costs.index'))
            ->assertOk()
            ->assertSee(__('logistics.shipment_costs'));
    }

    public function test_admin_can_add_cost_to_shipment(): void
    {
        $this->actingAsAdmin();

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-TEST-001',
            'transport_mode' => 'road',
            'status' => 'draft',
            'currency' => 'USD',
            'cargo_description' => '480 box',
        ]);

        $response = $this->post(route('shipments.costs.store', $shipment), [
            'item_name' => 'Damla transport',
            'invoice_number' => '45.Fatura',
            'expense_date' => '2026-06-19',
            'payee' => 'Vakıfbank',
            'country' => 'Türkiye',
            'amount' => 1750,
            'currency' => 'USD',
            'amount_try' => 80525.20,
            'notes' => 'Fatura bedeli TL ödeme',
            'status' => 'delivered',
            'redirect' => 'show',
        ]);

        $response->assertRedirect(route('shipments.show', $shipment));
        $this->assertDatabaseHas('shipment_costs', [
            'shipment_id' => $shipment->id,
            'item_name' => 'Damla transport',
            'invoice_number' => '45.Fatura',
            'amount' => 1750,
            'status' => 'delivered',
        ]);

        $shipment->refresh();
        $this->assertEquals(1750, (float) $shipment->total_cost);
    }

    public function test_delete_cost_updates_shipment_total(): void
    {
        $this->actingAsAdmin();

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-TEST-002',
            'transport_mode' => 'road',
            'status' => 'draft',
            'currency' => 'USD',
        ]);

        $cost = ShipmentCost::create([
            'shipment_id' => $shipment->id,
            'type' => 'expense',
            'item_name' => 'Test masraf',
            'description' => 'Test masraf',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        app(\App\Services\ShipmentCostService::class)->syncShipmentTotal($shipment);
        $shipment->refresh();
        $this->assertEquals(500, (float) $shipment->total_cost);

        $this->delete(route('shipments.costs.destroy', $cost))->assertRedirect();

        $shipment->refresh();
        $this->assertEquals(0, (float) $shipment->total_cost);
    }
}
