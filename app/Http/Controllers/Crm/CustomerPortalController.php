<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerPortalService;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:customers.edit');
    }

    public function store(Request $request, Customer $customer, CustomerPortalService $service)
    {
        $validated = $this->validatePortal($request, $customer, ! $customer->portalAccess?->user_id);

        $service->upsert($customer, $validated, (int) auth()->id());

        return back()->with('success', __('portal.access_saved'));
    }

    public function update(Request $request, Customer $customer, CustomerPortalService $service)
    {
        $validated = $this->validatePortal($request, $customer, false);

        $service->upsert($customer, $validated, (int) auth()->id());

        return back()->with('success', __('portal.access_saved'));
    }

    public function destroy(Customer $customer, CustomerPortalService $service)
    {
        $service->revoke($customer);

        return back()->with('success', __('portal.access_revoked'));
    }

    protected function validatePortal(Request $request, Customer $customer, bool $requirePassword): array
    {
        $rules = [
            'is_active' => 'nullable|boolean',
            'name' => 'nullable|string|max:150',
            'email' => 'required|email|max:255|unique:users,email,'.($customer->portalAccess?->user_id ?? 'NULL'),
            'view_orders' => 'nullable|boolean',
            'view_shipments' => 'nullable|boolean',
            'view_shipment_costs' => 'nullable|boolean',
            'view_directory' => 'nullable|boolean',
            'edit_profile' => 'nullable|boolean',
        ];

        if ($requirePassword || $request->filled('password')) {
            $rules['password'] = ($requirePassword ? 'required' : 'nullable').'|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        return [
            'is_active' => $request->boolean('is_active'),
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'view_orders' => $request->boolean('view_orders', true),
            'view_shipments' => $request->boolean('view_shipments', true),
            'view_shipment_costs' => $request->boolean('view_shipment_costs', true),
            'view_directory' => $request->boolean('view_directory', false),
            'edit_profile' => $request->boolean('edit_profile', true),
        ];
    }
}
