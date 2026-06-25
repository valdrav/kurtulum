<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentCost;

class ShipmentCostService
{
    public function syncShipmentTotal(Shipment $shipment): void
    {
        $total = (float) $shipment->costs()->sum('amount');

        $shipment->update(['total_cost' => $total]);
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return [
            'pending' => __('logistics.cost_status.pending'),
            'paid' => __('logistics.cost_status.paid'),
            'delivered' => __('logistics.cost_status.delivered'),
        ];
    }

    public function normalizePayload(array $data): array
    {
        $data['type'] = $data['type'] ?? 'expense';
        $data['currency'] = strtoupper($data['currency'] ?? 'USD');
        $data['status'] = $data['status'] ?? 'pending';
        $data['description'] = trim($data['item_name'] ?? $data['description'] ?? '');

        if (! empty($data['amount_try']) && ! empty($data['amount']) && (float) $data['amount'] > 0) {
            $data['exchange_rate'] = $data['exchange_rate']
                ?? round((float) $data['amount_try'] / (float) $data['amount'], 6);
        }

        if (($data['status'] ?? '') === 'paid' && empty($data['paid_at']) && ! empty($data['expense_date'])) {
            $data['paid_at'] = $data['expense_date'];
        }

        return $data;
    }

    public function createForShipment(Shipment $shipment, array $data): ShipmentCost
    {
        $data = $this->normalizePayload($data);
        $data['shipment_id'] = $shipment->id;
        $data['user_id'] = auth()->id();

        $cost = ShipmentCost::create($data);
        $this->syncShipmentTotal($shipment);

        return $cost;
    }

    public function updateCost(ShipmentCost $cost, array $data): ShipmentCost
    {
        $data = $this->normalizePayload($data);
        $cost->update($data);
        $this->syncShipmentTotal($cost->shipment);

        return $cost->fresh();
    }

    public function deleteCost(ShipmentCost $cost): void
    {
        $shipment = $cost->shipment;
        $cost->delete();

        if ($shipment) {
            $this->syncShipmentTotal($shipment);
        }
    }
}
