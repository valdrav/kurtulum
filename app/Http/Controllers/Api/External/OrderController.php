<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(external_api()->allows('orders'), 403);

        $orders = external_api()->ordersQuery()
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $o) => $this->summary($o)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(string $order)
    {
        abort_unless(external_api()->allows('orders'), 403);

        $order = external_api()->findOrder($order);
        $order->load(['items.product']);

        return response()->json(['data' => $this->detail($order)]);
    }

    protected function summary(Order $order): array
    {
        return [
            'id' => $order->uuid,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'order_date' => $order->order_date?->toDateString(),
            'delivery_date' => $order->delivery_date?->toDateString(),
            'currency' => $order->currency,
            'total_amount' => (float) $order->total_amount,
        ];
    }

    protected function detail(Order $order): array
    {
        return array_merge($this->summary($order), [
            'incoterm' => $order->incoterm,
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_country' => $order->shipping_country,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product?->name ?? $item->description,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->values(),
        ]);
    }
}
