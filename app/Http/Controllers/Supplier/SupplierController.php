<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Concerns\RequiresPermissions;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use RequiresPermissions;

    public function __construct()
    {
        $this->registerPermissions([
            'index|show' => 'suppliers.view',
            'create|store' => 'suppliers.create',
            'edit|update' => 'suppliers.edit',
            'destroy' => 'suppliers.delete',
        ]);
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::when($request->search, fn ($q, $s) => $q->where('company_name', 'like', "%{$s}%"))
            ->latest()->paginate(20);

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

    public function show(Supplier $supplier)
    {
        $supplier->load(['contacts', 'orders', 'documents']);
        return view('suppliers.show', compact('supplier'));
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

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
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

        return $validated;
    }
}
