<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(portal()->allows('orders'), 403);

        $orders = portal()->ordersQuery()
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->paginate(20)
            ->withQueryString();

        return view('portal.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless(portal()->allows('orders'), 403);
        portal()->assertOrderAccess($order);

        $order->load(['items']);

        return view('portal.orders.show', compact('order'));
    }
}
