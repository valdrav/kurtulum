<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentCost;
use App\Services\ShipmentCostService;
use Illuminate\Http\Request;

class ShipmentCostController extends Controller
{
    public function __construct(protected ShipmentCostService $costs)
    {
        $this->middleware('permission:shipments.view')->only(['index']);
        $this->middleware('permission:shipments.create')->only(['store']);
        $this->middleware('permission:shipments.edit')->only(['update']);
        $this->middleware('permission:shipments.delete|shipments.create')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $shipments = Shipment::query()
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'uuid', 'shipment_number', 'cargo_description', 'package_count']);

        $selectedShipment = null;
        if ($request->filled('shipment')) {
            $selectedShipment = Shipment::where('uuid', $request->shipment)
                ->orWhere('id', $request->shipment)
                ->first();
        }

        $baseQuery = ShipmentCost::query()
            ->when($selectedShipment, fn ($q) => $q->where('shipment_id', $selectedShipment->id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->invoice, fn ($q, $invoice) => $q->where('invoice_number', 'like', "%{$invoice}%"))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('payee', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            }))
            ->when($request->year, fn ($q, $year) => $q->whereYear('expense_date', $year));

        $totalsByCurrency = (clone $baseQuery)
            ->reorder()
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $items = (clone $baseQuery)
            ->with(['shipment', 'user'])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('logistics.shipments.costs.index', compact(
            'items', 'shipments', 'selectedShipment', 'totalsByCurrency'
        ));
    }

    public function store(Request $request, Shipment $shipment)
    {
        $validated = $this->validateCost($request);
        $this->costs->createForShipment($shipment, $validated);

        return $this->redirectAfterSave($request, $shipment, messageKey: 'messages.created');
    }

    public function storeFromIndex(Request $request)
    {
        $validated = $this->validateCost($request, requireShipment: true);
        $shipment = Shipment::findOrFail($validated['shipment_id']);
        unset($validated['shipment_id']);

        $this->costs->createForShipment($shipment, $validated);

        return redirect()
            ->route('shipments.costs.index', ['shipment' => $shipment->uuid])
            ->with('success', __('messages.created'));
    }

    public function update(Request $request, ShipmentCost $cost)
    {
        $validated = $this->validateCost($request);
        $this->costs->updateCost($cost, $validated);

        return $this->redirectAfterSave($request, $cost->shipment, $cost);
    }

    public function destroy(Request $request, ShipmentCost $cost)
    {
        $shipment = $cost->shipment;
        $this->costs->deleteCost($cost);

        if ($request->input('redirect') === 'show' && $shipment) {
            return redirect()->route('shipments.show', $shipment)->with('success', __('messages.deleted'));
        }

        return redirect()
            ->route('shipments.costs.index', $shipment ? ['shipment' => $shipment->uuid] : [])
            ->with('success', __('messages.deleted'));
    }

    protected function validateCost(Request $request, bool $requireShipment = false): array
    {
        $rules = [
            'item_name' => 'required|string|max:255',
            'invoice_number' => 'nullable|string|max:100',
            'expense_date' => 'nullable|date',
            'payee' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'amount_try' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
            'status' => 'required|in:pending,paid,delivered',
            'paid_at' => 'nullable|date',
            'type' => 'nullable|string|max:50',
        ];

        if ($requireShipment) {
            $rules['shipment_id'] = 'required|exists:shipments,id';
        }

        return $request->validate($rules);
    }

    protected function redirectAfterSave(Request $request, Shipment $shipment, ?ShipmentCost $cost = null, string $messageKey = 'messages.saved')
    {
        if ($request->input('redirect') === 'show') {
            return redirect()->route('shipments.show', $shipment)->with('success', __($messageKey));
        }

        return redirect()
            ->route('shipments.costs.index', ['shipment' => $shipment->uuid])
            ->with('success', __($messageKey));
    }
}
