<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class ShipmentDeletionService
{
    public function __construct(protected ShipmentCostService $costs) {}

    /** @return array{costs: int} */
    public function deleteShipment(Shipment $shipment): array
    {
        return DB::transaction(function () use ($shipment) {
            $summary = ['costs' => 0];

            $shipment->load('costs');

            foreach ($shipment->costs as $cost) {
                $this->costs->deleteCost($cost);
                $summary['costs']++;
            }

            $shipment->legs()->delete();
            $shipment->milestones()->delete();
            $shipment->delete();

            return $summary;
        });
    }
}
