<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(external_api()->allows('shipments'), 403);

        $shipments = external_api()->shipmentsQuery()
            ->when($request->search, fn ($q, $s) => $q->where('shipment_number', 'like', "%{$s}%"))
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json([
            'data' => $shipments->getCollection()->map(fn (Shipment $s) => $this->summary($s)),
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    public function show(string $shipment)
    {
        abort_unless(external_api()->allows('shipments'), 403);

        $shipment = external_api()->findShipment($shipment);
        $shipment->load(['order']);

        if (external_api()->allows('shipment_costs')) {
            $shipment->load(['costs']);
        }

        return response()->json(['data' => $this->detail($shipment)]);
    }

    protected function summary(Shipment $shipment): array
    {
        $data = [
            'id' => $shipment->uuid,
            'shipment_number' => $shipment->shipment_number,
            'transport_mode' => $shipment->transport_mode,
            'origin' => $shipment->origin,
            'destination' => $shipment->destination,
            'atd' => $shipment->atd?->toIso8601String(),
            'ata' => $shipment->ata?->toIso8601String(),
            'status' => $shipment->status,
        ];

        if (external_api()->allows('shipment_costs')) {
            $data['total_cost'] = (float) ($shipment->total_cost ?? 0);
            $data['currency'] = $shipment->currency;
        }

        return $data;
    }

    protected function detail(Shipment $shipment): array
    {
        $data = array_merge($this->summary($shipment), [
            'order_id' => $shipment->order?->uuid,
            'order_number' => $shipment->order?->order_number,
        ]);

        if (external_api()->allows('shipment_costs') && $shipment->relationLoaded('costs')) {
            $data['costs'] = $shipment->costs->map(fn ($cost) => [
                'item_name' => $cost->item_name,
                'description' => $cost->description,
                'type' => $cost->type,
                'amount' => (float) $cost->amount,
                'currency' => $cost->currency,
                'expense_date' => $cost->expense_date?->toDateString(),
                'status' => $cost->status,
            ])->values();
        }

        return $data;
    }
}
