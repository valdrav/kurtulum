<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Concerns\RequiresPermissions;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\CrmDeletionService;
use App\Services\OrderFinanceService;
use App\Services\SupplierProfileService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use RequiresPermissions;

    public function __construct()
    {
        $this->registerPermissions([
            'index|show' => 'suppliers.view',
            'create|store' => 'suppliers.create',
            'edit|update|backfillOrders' => 'suppliers.edit',
            'destroy' => 'suppliers.delete',
        ]);
    }

    public function index(Request $request, CrmDeletionService $deletion)
    {
        $suppliers = Supplier::query()
            ->with('account')
            ->withCount(['orders' => fn ($q) => $q->whereNotIn('status', ['cancelled'])])
            ->withSum(['orders as purchase_total_sum' => fn ($q) => $q->whereNotIn('status', ['cancelled'])], 'purchase_total')
            ->when($request->search, fn ($q, $s) => $q->where('company_name', 'like', "%{$s}%"))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('company_name')
            ->paginate(20)
            ->withQueryString();

        $suppliers->getCollection()->transform(function (Supplier $supplier) use ($deletion) {
            $supplier->setAttribute('deletion_block_reason', $deletion->supplierDeletionBlockReason($supplier));

            return $supplier;
        });

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSupplier($request);
        $validated['created_by'] = auth()->id();
        $supplier = Supplier::create($validated);
        return redirect()->route('suppliers.show', $supplier)->with('success', __('messages.created'));
    }

    public function show(Supplier $supplier, OrderFinanceService $orderFinance, SupplierProfileService $profile, CrmDeletionService $deletion)
    {
        $supplier->load(['contacts', 'documents', 'account']);
        $account = $supplier->account ?? $orderFinance->ensureSupplierAccount($supplier);
        $summary = $profile->summary($supplier);
        $unlinkedOrderCount = $profile->unlinkedOrderCount($supplier);
        $orders = $profile->orders($supplier);
        $products = $profile->aggregatedProducts($supplier);
        $productLines = $profile->productLines($supplier);
        $payments = $profile->payments($supplier);
        $shipmentCosts = $profile->shipmentCosts($supplier);
        $deletionBlockReason = $deletion->supplierDeletionBlockReason($supplier);

        return view('suppliers.show', compact(
            'supplier', 'account', 'summary', 'unlinkedOrderCount', 'orders', 'products',
            'productLines', 'payments', 'shipmentCosts', 'deletionBlockReason'
        ));
    }

    public function backfillOrders(Supplier $supplier, SupplierProfileService $profile)
    {
        $count = $profile->backfillOrderLinks($supplier);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', __('suppliers.backfill_done', ['count' => $count]));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validateSupplier($request));
        return redirect()->route('suppliers.show', $supplier)->with('success', __('messages.updated'));
    }

    public function destroy(Supplier $supplier, CrmDeletionService $deletion)
    {
        if ($reason = $deletion->supplierDeletionBlockReason($supplier)) {
            return back()->with('warning', $reason);
        }

        $deletion->deleteSupplier($supplier);

        return redirect()->route('suppliers.index')->with('success', __('messages.deleted'));
    }

    protected function validateSupplier(Request $request): array
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:3',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'type' => 'nullable|in:manufacturer,trader,logistics,service',
            'status' => 'nullable|in:active,inactive',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
        ]);

        $validated['type'] = $validated['type'] ?? 'trader';
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['currency'] = $validated['currency'] ?? 'USD';

        if (! empty($validated['country'])) {
            $validated['country'] = country_iso2($validated['country']);
        }

        return $validated;
    }
}
