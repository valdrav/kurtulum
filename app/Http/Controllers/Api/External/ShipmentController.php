<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        abort_unless(external_api()->allows('edit_shipments'), 403);

        $validated = $this->validateWritable($request);
        $order = $this->resolveOrder($request->input('order_id'));

        $shipment = DB::transaction(function () use ($validated, $order) {
            $data = $validated;
            $data['shipment_number'] = $data['shipment_number'] ?? $this->generateNumber('SHP');
            $data['customer_id'] = external_api()->customerId();
            $data['order_id'] = $order?->id;
            $data['created_by'] = external_api()->connection->created_by;

            if ($order && empty($data['currency'])) {
                $data['currency'] = $order->currency;
            }

            return Shipment::create($data);
        });

        $shipment->load(['order']);

        return response()->json(['data' => $this->detail($shipment)], 201);
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

    public function update(Request $request, string $shipment)
    {
        abort_unless(external_api()->allows('edit_shipments'), 403);

        $shipment = external_api()->findShipment($shipment);
        $validated = $this->validateWritable($request, $shipment);

        if ($request->filled('order_id')) {
            $order = $this->resolveOrder($request->input('order_id'));
            $validated['order_id'] = $order?->id;
        }

        $shipment->update($validated);
        $shipment->load(['order', 'costs']);

        return response()->json(['data' => $this->detail($shipment->fresh())]);
    }

    protected function resolveOrder(?string $orderUuid): ?Order
    {
        if (! $orderUuid) {
            return null;
        }

        return external_api()->findOrder($orderUuid);
    }

    protected function validateWritable(Request $request, ?Shipment $shipment = null): array
    {
        $uniqueRule = Rule::unique('shipments', 'shipment_number');
        if ($shipment) {
            $uniqueRule = $uniqueRule->ignore($shipment->id);
        }

        return $request->validate([
            'order_id' => 'nullable|uuid',
            'shipment_number' => ['nullable', 'string', 'max:50', $uniqueRule],
            'transport_mode' => ($shipment ? 'nullable' : 'required').'|in:road,sea,air,rail,multimodal',
            'status' => ['nullable', shipment_statuses_rule()],
            'incoterm' => 'nullable|string|max:10',
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'etd' => 'nullable|date',
            'eta' => 'nullable|date',
            'atd' => 'nullable|date',
            'ata' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'cargo_description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
            'bl_number' => 'nullable|string|max:100',
            'awb_number' => 'nullable|string|max:100',
        ]);
    }

    protected function generateNumber(string $prefix): string
    {
        return $prefix.'-'.date('Y').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
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
            'eta' => $shipment->eta?->toDateString(),
            'status' => $shipment->status,
            'order_id' => $shipment->order?->uuid,
            'order_number' => $shipment->order?->order_number,
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
            'incoterm' => $shipment->incoterm,
            'etd' => $shipment->etd?->toDateString(),
            'cargo_description' => $shipment->cargo_description,
            'notes' => $shipment->notes,
            'bl_number' => $shipment->bl_number,
            'awb_number' => $shipment->awb_number,
        ]);

        if (external_api()->allows('shipment_costs') && $shipment->relationLoaded('costs')) {
            $data['costs'] = $shipment->costs->map(fn ($cost) => [
                'id' => $cost->uuid,
                'item_name' => $cost->item_name,
                'description' => $cost->description,
                'type' => $cost->type,
                'amount' => (float) $cost->amount,
                'currency' => $cost->currency,
                'expense_date' => $cost->expense_date?->toDateString(),
                'status' => $cost->status,
                'notes' => $cost->notes,
            ])->values();
        }

        return $data;
    }
}
