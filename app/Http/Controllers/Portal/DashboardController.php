<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;

class DashboardController extends Controller
{
    public function index()
    {
        $ctx = portal();
        $customer = $ctx->customer();

        $stats = [
            'orders' => $ctx->allows('orders') ? $ctx->ordersQuery()->count() : 0,
            'shipments' => $ctx->allows('shipments') ? $ctx->shipmentsQuery()->count() : 0,
        ];

        $recentOrders = $ctx->allows('orders')
            ? $ctx->ordersQuery()->limit(5)->get()
            : collect();

        $recentShipments = $ctx->allows('shipments')
            ? $ctx->shipmentsQuery()->limit(5)->get()
            : collect();

        return view('portal.dashboard', compact('customer', 'stats', 'recentOrders', 'recentShipments'));
    }
}
