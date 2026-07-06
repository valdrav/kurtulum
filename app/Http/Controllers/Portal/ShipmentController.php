<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(portal()->allows('shipments'), 403);

        $shipments = portal()->shipmentsQuery()
            ->when($request->search, fn ($q, $s) => $q->where('shipment_number', 'like', "%{$s}%"))
            ->paginate(20)
            ->withQueryString();

        return view('portal.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        abort_unless(portal()->allows('shipments'), 403);
        portal()->assertShipmentAccess($shipment);

        $shipment->load(['order']);

        if (portal()->allows('shipment_costs')) {
            $shipment->load(['costs']);
        }

        return view('portal.shipments.show', [
            'shipment' => $shipment,
            'showCosts' => portal()->allows('shipment_costs'),
        ]);
    }
}
