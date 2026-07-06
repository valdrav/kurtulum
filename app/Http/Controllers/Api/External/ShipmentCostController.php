<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\ShipmentCost;
use App\Services\ShipmentCostService;
use Illuminate\Http\Request;

class ShipmentCostController extends Controller
{
    public function __construct(protected ShipmentCostService $costs) {}

    public function store(Request $request, string $shipment)
    {
        abort_unless(external_api()->allows('shipment_costs'), 403);
        abort_unless(external_api()->allows('edit_shipment_costs'), 403);

        $shipment = external_api()->findShipment($shipment);
        $validated = $this->validateCost($request);

        $cost = $this->costs->createForShipment($shipment, $validated);

        return response()->json(['data' => $this->payload($cost)], 201);
    }

    public function update(Request $request, string $cost)
    {
        abort_unless(external_api()->allows('shipment_costs'), 403);
        abort_unless(external_api()->allows('edit_shipment_costs'), 403);

        $cost = external_api()->findCost($cost);
        $validated = $this->validateCost($request);

        $this->costs->updateCost($cost, $validated);

        return response()->json(['data' => $this->payload($cost->fresh())]);
    }

    public function destroy(string $cost)
    {
        abort_unless(external_api()->allows('shipment_costs'), 403);
        abort_unless(external_api()->allows('edit_shipment_costs'), 403);

        $cost = external_api()->findCost($cost);
        $this->costs->deleteCost($cost);

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validateCost(Request $request): array
    {
        $request->merge([
            'expense_date' => $request->input('expense_date') ?: null,
            'description' => $request->input('description') ?: null,
            'notes' => $request->input('notes') ?: null,
            'type' => $request->input('type') ?: 'expense',
        ]);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'expense_date' => 'nullable|date',
            'status' => 'required|in:pending,paid,delivered',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (empty($validated['expense_date'])) {
            $validated['expense_date'] = now()->toDateString();
        }

        return $validated;
    }

    protected function payload(ShipmentCost $cost): array
    {
        return [
            'id' => $cost->uuid,
            'item_name' => $cost->item_name,
            'description' => $cost->description,
            'type' => $cost->type,
            'amount' => (float) $cost->amount,
            'currency' => $cost->currency,
            'expense_date' => $cost->expense_date?->toDateString(),
            'status' => $cost->status,
            'notes' => $cost->notes,
        ];
    }
}
