<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Concerns\RequiresPermissions;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use RequiresPermissions;

    public function __construct()
    {
        $this->registerPermissions([
            'index|show' => 'customers.view',
            'create|store' => 'customers.create',
            'edit|update' => 'customers.edit',
            'destroy' => 'customers.delete',
        ]);
    }
    public function index(Request $request)
    {
        $customers = Customer::with('assignedUser')
            ->when($request->search, fn ($q, $s) => $q->where('company_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20);

        return view('crm.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('crm.customers.form', ['customer' => new Customer()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCustomer($request);
        $validated['created_by'] = auth()->id();
        $customer = Customer::create($validated);

        return redirect()->route('customers.show', $customer)->with('success', __('messages.created'));
    }

    public function show(Customer $customer)
    {
        $customer->load(['contacts', 'activities.user', 'orders', 'shipments', 'documents']);
        return view('crm.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('crm.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateCustomer($request));
        return redirect()->route('customers.show', $customer)->with('success', __('messages.updated'));
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', __('messages.deleted'));
    }

    protected function validateCustomer(Request $request): array
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:3',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'type' => 'nullable|in:buyer,agent,distributor,partner',
            'status' => 'nullable|in:active,inactive,prospect',
            'currency' => 'nullable|string|size:3',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['type'] = $validated['type'] ?? 'buyer';
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['currency'] = $validated['currency'] ?? 'TRY';

        if (! empty($validated['country'])) {
            $validated['country'] = country_iso2($validated['country']);
        }

        return $validated;
    }
}
