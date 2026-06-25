<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\ShipmentLeg;
use App\Models\ShipmentMilestone;
use App\Models\Vehicle;
use App\Models\Vessel;
use App\Services\OrderShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $shipments = Shipment::with(['customer', 'order', 'originPort', 'destinationPort'])
            ->when($request->mode, fn ($q, $m) => $q->where('transport_mode', $m))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('shipment_number', 'like', "%{$s}%")
                ->orWhere('bl_number', 'like', "%{$s}%")
                ->orWhere('awb_number', 'like', "%{$s}%"))
            ->latest()->paginate(20);

        return view('logistics.shipments.index', compact('shipments'));
    }

    public function create(Request $request)
    {
        $shipment = new Shipment([
            'status' => 'draft',
            'transport_mode' => 'road',
            'currency' => 'USD',
        ]);

        if ($request->filled('order')) {
            $order = Order::query()
                ->with('items')
                ->where(function ($q) use ($request) {
                    $q->where('uuid', $request->order)->orWhere('id', $request->order);
                })
                ->first();

            if ($order) {
                $shipment->fill([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'incoterm' => $order->incoterm,
                    'currency' => $order->currency ?? 'USD',
                    'eta' => $order->delivery_date,
                    'cargo_description' => app(OrderShipmentService::class)->cargoDescriptionForOrder($order),
                    'notes' => $order->notes,
                ]);
            }
        }

        return view('logistics.shipments.form', $this->formData($shipment));
    }

    public function store(Request $request)
    {
        $validated = $this->validateShipment($request);

        $shipment = DB::transaction(function () use ($validated, $request) {
            $data = collect($validated)->except(['legs', 'milestones'])->toArray();
            $data['shipment_number'] = $this->generateNumber('SHP');
            $data['created_by'] = auth()->id();
            $shipment = Shipment::create($data);
            $this->syncLegs($shipment, $request->input('legs', []));
            $this->syncMilestones($shipment, $request->input('milestones', []));
            return $shipment;
        });

        return redirect()->route('shipments.show', $shipment)->with('success', __('messages.created'));
    }

    public function syncFromOrders(OrderShipmentService $orderShipments)
    {
        $count = $orderShipments->syncOrdersWithoutShipments();

        return redirect()
            ->route('shipments.index')
            ->with('success', __('orders.shipments_synced', ['count' => $count]));
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'customer', 'order', 'originPort', 'destinationPort', 'vessel',
            'vehicle', 'driver', 'legs', 'containers', 'costs.user', 'milestones',
            'customsDeclarations', 'documents', 'assignedUser',
        ]);

        $nextStatuses = shipment_next_statuses(
            in_array($shipment->transport_mode, ['sea', 'road'], true) ? $shipment->transport_mode : 'default',
            $shipment->status
        );

        return view('logistics.shipments.show', compact('shipment', 'nextStatuses'));
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status' => ['required', shipment_statuses_rule()],
            'status_location' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
            'manual' => 'nullable|boolean',
        ]);

        $mode = in_array($shipment->transport_mode, ['sea', 'road'], true)
            ? $shipment->transport_mode
            : 'default';

        $allowed = shipment_next_statuses($mode, $shipment->status);
        $isManual = $request->boolean('manual') && can_access('shipments.edit');

        if (
            ! $isManual
            && $validated['status'] !== $shipment->status
            && ! in_array($validated['status'], $allowed, true)
        ) {
            return back()->withErrors(['status' => __('logistics.status_transition_blocked')]);
        }

        $updates = [
            'status' => $validated['status'],
            'status_location' => trim($validated['status_location'] ?? '') ?: null,
            'status_updated_at' => now(),
        ];

        if ($validated['status'] === 'in_transit' && ! $shipment->atd) {
            $updates['atd'] = now();
        }

        if (in_array($validated['status'], ['delivered', 'completed'], true) && ! $shipment->ata) {
            $updates['ata'] = now();
        }

        $noteParts = [];
        if ($updates['status_location']) {
            $noteParts[] = shipment_status_display($validated['status'], $updates['status_location']);
        } else {
            $noteParts[] = status_label($validated['status'], 'shipment');
        }

        if ($validated['note'] ?? null) {
            $noteParts[] = $validated['note'];
        }

        if ($validated['status'] !== $shipment->status || $updates['status_location'] !== $shipment->status_location) {
            $updates['notes'] = trim(($shipment->notes ? $shipment->notes . "\n" : '')
                . now()->format('d.m.Y H:i') . ' — ' . implode(' — ', $noteParts));
        } elseif ($validated['note'] ?? null) {
            $updates['notes'] = trim(($shipment->notes ? $shipment->notes . "\n" : '')
                . now()->format('d.m.Y H:i') . ' — ' . $validated['note']);
        }

        $shipment->update($updates);

        return back()->with('success', __('messages.updated'));
    }

    public function edit(Shipment $shipment)
    {
        $shipment->load(['legs', 'milestones']);
        return view('logistics.shipments.form', $this->formData($shipment));
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $this->validateShipment($request);

        DB::transaction(function () use ($shipment, $validated, $request) {
            $shipment->update(collect($validated)->except(['legs', 'milestones'])->toArray());
            $shipment->legs()->delete();
            $this->syncLegs($shipment, $request->input('legs', []));
        });

        return redirect()->route('shipments.show', $shipment)->with('success', __('messages.updated'));
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return redirect()->route('shipments.index')->with('success', __('messages.deleted'));
    }

    public function tracking()
    {
        $shipments = Shipment::with(['customer', 'milestones'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('eta')->get();

        return view('logistics.tracking', compact('shipments'));
    }

    protected function formData(Shipment $shipment): array
    {
        $ports = Port::query()->orderBy('name')->get();

        return [
            'shipment' => $shipment,
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'orders' => Order::whereNotIn('status', ['cancelled'])->latest()->limit(100)->get(),
            'ports' => $ports,
            'portsJson' => $ports->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'type' => $p->type,
                'country' => $p->country,
                'label' => port_display_label($p),
            ])->values(),
            'vessels' => Vessel::orderBy('name')->get(),
            'vehicles' => Vehicle::where('status', '!=', 'maintenance')->get(),
            'drivers' => Driver::where('status', '!=', 'off_duty')->get(),
        ];
    }

    protected function validateShipment(Request $request): array
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'customer_id' => 'nullable|exists:customers,id',
            'transport_mode' => 'required|in:road,sea,air,rail,multimodal',
            'status' => ['nullable', shipment_statuses_rule()],
            'status_location' => 'nullable|string|max:255',
            'incoterm' => 'nullable|string|max:10',
            'origin' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'origin_port_id' => 'nullable|exists:ports,id',
            'destination_port_id' => 'nullable|exists:ports,id',
            'etd' => 'nullable|date',
            'eta' => 'nullable|date',
            'atd' => 'nullable|date',
            'ata' => 'nullable|date',
            'bl_number' => 'nullable|string|max:100',
            'awb_number' => 'nullable|string|max:100',
            'cmr_number' => 'nullable|string|max:100',
            'vessel_id' => 'nullable|exists:vessels,id',
            'voyage_number' => 'nullable|string|max:100',
            'flight_number' => 'nullable|string|max:50',
            'airline' => 'nullable|string|max:100',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'carrier' => 'nullable|string|max:255',
            'forwarder' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'total_weight_kg' => 'nullable|numeric|min:0',
            'total_volume_cbm' => 'nullable|numeric|min:0',
            'package_count' => 'nullable|integer|min:0',
            'cargo_description' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['currency'] = $validated['currency'] ?? 'USD';

        return $validated;
    }

    protected function syncLegs(Shipment $shipment, array $legs): void
    {
        foreach ($legs as $i => $leg) {
            if (empty($leg['transport_mode'])) continue;
            ShipmentLeg::create([
                'shipment_id' => $shipment->id,
                'leg_order' => $i + 1,
                ...collect($leg)->only([
                    'transport_mode', 'origin', 'destination', 'etd', 'eta',
                    'atd', 'ata', 'carrier', 'reference_number', 'status',
                ])->toArray(),
            ]);
        }
    }

    protected function syncMilestones(Shipment $shipment, array $milestones): void
    {
        $defaults = [
            ['title' => __('logistics.milestone_booking'), 'status' => 'pending'],
            ['title' => __('logistics.milestone_loading'), 'status' => 'pending'],
            ['title' => __('logistics.milestone_departure'), 'status' => 'pending'],
            ['title' => __('logistics.milestone_arrival'), 'status' => 'pending'],
            ['title' => __('logistics.milestone_customs'), 'status' => 'pending'],
            ['title' => __('logistics.milestone_delivery'), 'status' => 'pending'],
        ];

        foreach ($milestones ?: $defaults as $m) {
            ShipmentMilestone::create([
                'shipment_id' => $shipment->id,
                'name' => $m['title'],
                'status' => $m['status'] ?? 'pending',
            ]);
        }
    }
}
