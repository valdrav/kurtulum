<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        abort_unless(external_api()->allows('edit_orders'), 403);

        $validated = $this->validateWritable($request);
        $items = $request->input('items', []);

        $order = DB::transaction(function () use ($validated, $items) {
            $data = collect($validated)->except('items')->toArray();
            $data['customer_id'] = external_api()->customerId();
            $data['order_number'] = $data['order_number'] ?? $this->generateNumber('ORD');

            $order = Order::create($data);
            $this->syncItems($order, $items);
            $this->recalculateTotal($order);

            return $order->fresh(['items.product']);
        });

        return response()->json(['data' => $this->detail($order)], 201);
    }

    public function show(string $order)
    {
        abort_unless(external_api()->allows('orders'), 403);

        $order = external_api()->findOrder($order);
        $order->load(['items.product']);

        return response()->json(['data' => $this->detail($order)]);
    }

    public function update(Request $request, string $order)
    {
        abort_unless(external_api()->allows('edit_orders'), 403);

        $order = external_api()->findOrder($order);
        $validated = $this->validateWritable($request, $order);

        DB::transaction(function () use ($order, $validated, $request) {
            $order->update(collect($validated)->except('items')->toArray());

            if ($request->has('items')) {
                $order->items()->delete();
                $this->syncItems($order, $request->input('items', []));
                $this->recalculateTotal($order);
            }
        });

        $order->load(['items.product']);

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    protected function validateWritable(Request $request, ?Order $order = null): array
    {
        $validated = $request->validate([
            'order_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('orders', 'order_number')->ignore($order?->id),
            ],
            'status' => 'nullable|in:draft,confirmed,production,ready,shipped,delivered,cancelled',
            'incoterm' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
            'order_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'shipping_address' => 'nullable|string|max:2000',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_country' => 'nullable|string|max:100',
            'items' => ($order ? 'nullable' : 'required').'|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit' => 'nullable|string|max:20',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        $validated['status'] = $validated['status'] ?? ($order?->status ?? 'draft');
        $validated['currency'] = $validated['currency'] ?? ($order?->currency ?? external_api()->customer()->currency ?? 'USD');
        $validated['order_date'] = $validated['order_date'] ?? ($order?->order_date?->toDateString() ?? now()->toDateString());

        if (empty(trim($validated['order_number'] ?? ''))) {
            unset($validated['order_number']);
        }

        return $validated;
    }

    protected function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            if (empty(trim($item['description'] ?? ''))) {
                continue;
            }

            $qty = (float) ($item['quantity'] ?? 0);
            $salePrice = (float) ($item['unit_price'] ?? 0);
            $saleTotal = round($qty * $salePrice, 2);

            OrderItem::create([
                'order_id' => $order->id,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $salePrice,
                'sale_unit_price' => $salePrice,
                'total' => $saleTotal,
                'purchase_total' => 0,
                'margin_amount' => $saleTotal,
            ]);
        }
    }

    protected function recalculateTotal(Order $order): void
    {
        $saleTotal = (float) $order->items()->sum('total');

        $order->update([
            'subtotal' => $saleTotal,
            'sale_total' => $saleTotal,
            'purchase_total' => 0,
            'margin_total' => $saleTotal,
            'total_amount' => $saleTotal,
        ]);
    }

    protected function generateNumber(string $prefix): string
    {
        return $prefix.'-'.date('Y').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
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
            'notes' => $order->notes,
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
