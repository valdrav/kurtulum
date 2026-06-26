<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Concerns\RequiresPermissions;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\CsvExportService;
use App\Services\OrderFinanceService;
use App\Services\OrderLifecycleService;
use App\Services\OrderShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use RequiresPermissions;

    public function __construct()
    {
        $this->registerPermissions([
            'index|show|export' => 'orders.view',
            'create|store|duplicate' => 'orders.create',
            'edit|update|cancel' => 'orders.edit',
            'destroy|restore' => 'orders.delete',
        ]);
    }

    public function index(Request $request)
    {
        $query = Order::with(['customer', 'supplier']);

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $orders = $query
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->supplier, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()->paginate(20);

        $suppliers = Supplier::where('status', 'active')->orderBy('company_name')->get(['id', 'company_name']);

        return view('orders.index', compact('orders', 'suppliers'));
    }

    public function export(Request $request, CsvExportService $csv)
    {
        $orders = Order::with(['customer', 'supplier'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->supplier, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()->get();

        return $csv->download('siparisler-' . now()->format('Y-m-d') . '.csv', [
            'Sipariş No', 'Müşteri', 'Tedarikçi', 'Durum', 'Tarih', 'Para Birimi', 'Alış', 'Satış', 'Marj',
        ], $orders->map(fn (Order $o) => [
            $o->order_number,
            $o->customer?->company_name ?? '',
            $o->supplier?->company_name ?? '',
            status_label($o->status, 'order'),
            $o->order_date?->format('d.m.Y') ?? '',
            $o->currency,
            $o->purchase_total,
            $o->total_amount,
            $o->margin_total,
        ]));
    }

    public function cancel(Order $order, OrderLifecycleService $lifecycle)
    {
        if ($order->status === 'cancelled') {
            return back()->with('warning', __('orders.already_cancelled'));
        }

        $lifecycle->cancel($order);

        return redirect()->route('orders.show', $order)
            ->with('success', __('orders.cancelled'))
            ->with('warning', __('orders.cancelled_finance_notice'));
    }

    public function duplicate(Order $order, OrderLifecycleService $lifecycle)
    {
        $copy = $lifecycle->duplicate($order);

        return redirect()->route('orders.edit', $copy)->with('success', __('orders.duplicated'));
    }

    public function restore(int $orderId, OrderLifecycleService $lifecycle)
    {
        $order = Order::onlyTrashed()->findOrFail($orderId);
        $lifecycle->restore($order);

        return redirect()->route('orders.show', $order)->with('success', __('orders.restored'));
    }

    public function create(Request $request)
    {
        $order = new Order();
        if ($request->filled('supplier_id')) {
            $order->supplier_id = (int) $request->supplier_id;
        }
        if ($request->filled('customer_id')) {
            $order->customer_id = (int) $request->customer_id;
        }

        return view('orders.form', [
            'order' => $order,
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('company_name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_price', 'unit']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);

        $order = DB::transaction(function () use ($validated, $request) {
            $orderData = collect($validated)->except('items')->toArray();
            $orderData['order_number'] = $validated['order_number'] ?? $this->generateNumber('ORD');
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
        $fxRates = fx_snapshot_rates();
        $orderExpenses = \App\Models\IncomeExpense::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->latest('transaction_date')
            ->get();

        return view('orders.show', compact(
            'order', 'finance', 'customerAccount', 'supplierAccount',
            'treasuryAccounts', 'collectionMethods', 'paymentMethods', 'fxRates', 'orderExpenses'
        ));
    }

    public function edit(Order $order)
    {
        $order->load('items');
        return view('orders.form', [
            'order' => $order,
            'customers' => Customer::where('status', 'active')->orderBy('company_name')->get(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('company_name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit_price', 'unit']),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $this->validateOrder($request, $order);

        $oldStatus = $order->status;

        DB::transaction(function () use ($order, $validated, $request, $oldStatus) {
            $order->update(collect($validated)->except('items')->toArray());
            $order->items()->delete();
            $this->syncItems($order, $request->input('items', []));
            $this->recalculateTotal($order);

            $fresh = $order->fresh();

            if ($fresh->status === 'confirmed' && $oldStatus !== 'confirmed') {
                app(OrderFinanceService::class)->postOrderLedger($fresh);
            } elseif ($fresh->finance_posted_at || $oldStatus === 'confirmed') {
                app(OrderFinanceService::class)->resyncOrderLedger($fresh);
            }

            app(OrderShipmentService::class)->ensureShipmentForOrder($fresh);
        });

        return redirect()->route('orders.show', $order)->with('success', __('messages.updated'));
    }

    public function destroy(int $orderId)
    {
        $order = Order::withTrashed()->findOrFail($orderId);

        if ($order->trashed()) {
            return redirect()->route('orders.index', ['trashed' => 1])
                ->with('info', __('orders.already_deleted'));
        }

        $summary = app(OrderFinanceService::class)->deleteOrder($order);

        $redirect = redirect()->route('orders.index')->with('success', __('messages.deleted'));

        if ($this->orderDeleteHadFinanceImpact($summary)) {
            $redirect->with('warning', __('orders.deleted_finance_notice', [
                'collections' => $summary['collections'],
                'payments' => $summary['payments'],
                'income_expenses' => $summary['income_expenses'],
                'shipments' => $summary['shipments'],
                'finance' => $summary['finance_reversed'] ? __('orders.deleted_finance_reversed') : '',
            ]));
        }

        return $redirect;
    }

    protected function orderDeleteHadFinanceImpact(array $summary): bool
    {
        return $summary['finance_reversed']
            || $summary['collections'] > 0
            || $summary['payments'] > 0
            || $summary['income_expenses'] > 0
            || $summary['shipments'] > 0;
    }

    protected function validateOrder(Request $request, ?Order $order = null): array
    {
        $validated = $request->validate([
            'order_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('orders', 'order_number')->ignore($order?->id),
            ],
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

        $hasPurchase = collect($request->input('items', []))->contains(
            fn ($item) => (float) ($item['purchase_unit_price'] ?? 0) > 0
                || (float) ($item['purchase_total'] ?? 0) > 0
        );

        if ($hasPurchase && empty($validated['supplier_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'supplier_id' => [__('orders.supplier_required_for_purchase')],
            ]);
        }

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
