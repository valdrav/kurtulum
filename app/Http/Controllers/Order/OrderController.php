<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Concerns\RequiresPermissions;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\OrderFinanceService;
use App\Services\OrderShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use RequiresPermissions;

    public function __construct()
    {
        $this->registerPermissions([
            'index|show' => 'orders.view',
            'create|store' => 'orders.create',
            'edit|update' => 'orders.edit',
            'destroy' => 'orders.delete',
        ]);
    }

    public function index(Request $request)
    {
        $orders = Order::with(['customer'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        return view('orders.form', [
            'order' => new Order(),
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('company_name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);

        $order = DB::transaction(function () use ($validated, $request) {
            $orderData = collect($validated)->except('items')->toArray();
            $orderData['order_number'] = $this->generateNumber('ORD');
            $order = Order::create($orderData);
            $this->syncItems($order, $request->input('items', []));
            $this->recalculateTotal($order);

            if ($order->status === 'confirmed') {
                app(OrderFinanceService::class)->postOrderLedger($order->fresh());
            }

            app(OrderShipmentService::class)->ensureShipmentForOrder($order->fresh());

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('success', __('messages.created'));
    }

    public function show(Order $order, OrderFinanceService $orderFinance)
    {
        $order->load(['customer', 'supplier', 'items.product', 'shipments', 'documents', 'collections', 'payments']);
        $finance = $orderFinance->financeSummary($order);
        $customerAccount = $order->customer ? $orderFinance->ensureCustomerAccount($order->customer) : null;
        $supplierAccount = $order->supplier ? $orderFinance->ensureSupplierAccount($order->supplier) : null;
        $treasuryAccounts = company_treasury()->accounts();
        $collectionMethods = payment_methods()->forCollection();
        $paymentMethods = payment_methods()->forPayment();

        return view('orders.show', compact(
            'order', 'finance', 'customerAccount', 'supplierAccount',
            'treasuryAccounts', 'collectionMethods', 'paymentMethods'
        ));
    }

    public function edit(Order $order)
    {
        $order->load('items');
        return view('orders.form', [
            'order' => $order,
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('company_name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $this->validateOrder($request);

        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $validated, $request, $oldStatus) {
            $order->update(collect($validated)->except('items')->toArray());
            $order->items()->delete();
            $this->syncItems($order, $request->input('items', []));
            $this->recalculateTotal($order);

            if ($order->fresh()->status === 'confirmed' && $oldStatus !== 'confirmed') {
                app(OrderFinanceService::class)->postOrderLedger($order->fresh());
            }

            app(OrderShipmentService::class)->ensureShipmentForOrder($order->fresh());
        });

        return redirect()->route('orders.show', $order)->with('success', __('messages.updated'));
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', __('messages.deleted'));
    }

    protected function validateOrder(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'status' => 'nullable|in:draft,confirmed,production,ready,shipped,delivered,cancelled',
            'incoterm' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
            'order_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.purchase_unit_price' => 'nullable|numeric|min:0',
            'items.*.purchase_discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sale_unit_price' => 'nullable|numeric|min:0',
            'items.*.product_id' => 'nullable|exists:products,id',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['order_date'] = $validated['order_date'] ?? now()->toDateString();

        return $validated;
    }

    protected function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            if (empty(trim($item['description'] ?? ''))) {
                continue;
            }
            $qty = (float) $item['quantity'];
            $purchasePrice = (float) ($item['purchase_unit_price'] ?? 0);
            $discountPct = (float) ($item['purchase_discount_percent'] ?? 0);
            $salePrice = (float) ($item['sale_unit_price'] ?? $item['unit_price'] ?? 0);

            $purchaseNet = $purchasePrice * (1 - ($discountPct / 100));
            $purchaseTotal = round($qty * $purchaseNet, 2);
            $saleTotal = round($qty * $salePrice, 2);
            $margin = round($saleTotal - $purchaseTotal, 2);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => $salePrice,
                'purchase_unit_price' => $purchasePrice,
                'purchase_discount_percent' => $discountPct,
                'sale_unit_price' => $salePrice,
                'total' => $saleTotal,
                'purchase_total' => $purchaseTotal,
                'margin_amount' => $margin,
            ]);
        }
    }

    protected function recalculateTotal(Order $order): void
    {
        $saleTotal = $order->items()->sum('total');
        $purchaseTotal = $order->items()->sum('purchase_total');
        $marginTotal = $order->items()->sum('margin_amount');

        $order->update([
            'subtotal' => $saleTotal,
            'sale_total' => $saleTotal,
            'purchase_total' => $purchaseTotal,
            'margin_total' => $marginTotal,
            'total_amount' => $saleTotal + $order->tax_amount - $order->discount_amount,
        ]);
    }
}
