<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ExternalApiConnection;
use App\Models\Setting;
use App\Services\ExternalApiConnectionService;
use Illuminate\Http\Request;

class ExternalApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.view');
        $this->middleware('permission:settings.edit')->only([
            'updateGlobal',
            'store',
            'update',
            'destroy',
            'regenerate',
        ]);
    }

    public function index()
    {
        $settings = [
            'external_api_enabled' => Setting::get('external_api_enabled', '0'),
        ];

        $connections = ExternalApiConnection::query()
            ->with('customer')
            ->latest()
            ->get();

        $customers = Customer::query()
            ->where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $baseUrl = url('/api/v1');

        return view('settings.external-api', compact('settings', 'connections', 'customers', 'baseUrl'));
    }

    public function updateGlobal(Request $request)
    {
        Setting::set('external_api_enabled', $request->boolean('external_api_enabled') ? '1' : '0', 'integrations');

        return back()->with('success', __('messages.saved'));
    }

    public function store(Request $request, ExternalApiConnectionService $service)
    {
        $validated = $this->validateConnection($request);

        $result = $service->create($validated, (int) auth()->id());

        return back()
            ->with('success', __('external_api.connection_created'))
            ->with('new_api_token', $result['plain_token'])
            ->with('new_api_connection', $result['connection']->name);
    }

    public function update(Request $request, ExternalApiConnection $connection, ExternalApiConnectionService $service)
    {
        $validated = $this->validateConnection($request);

        $service->update($connection, $validated);

        return back()->with('success', __('external_api.connection_updated'));
    }

    public function destroy(ExternalApiConnection $connection)
    {
        $connection->delete();

        return back()->with('success', __('external_api.connection_deleted'));
    }

    public function regenerate(ExternalApiConnection $connection, ExternalApiConnectionService $service)
    {
        $result = $service->regenerateToken($connection);

        return back()
            ->with('success', __('external_api.token_regenerated'))
            ->with('new_api_token', $result['plain_token'])
            ->with('new_api_connection', $result['connection']->name);
    }

    protected function validateConnection(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'customer_id' => 'required|exists:customers,id',
            'is_active' => 'nullable|boolean',
            'view_customer' => 'nullable|boolean',
            'edit_customer' => 'nullable|boolean',
            'view_directory' => 'nullable|boolean',
            'edit_directory' => 'nullable|boolean',
            'view_orders' => 'nullable|boolean',
            'view_shipments' => 'nullable|boolean',
            'view_shipment_costs' => 'nullable|boolean',
        ]);

        return [
            'name' => $validated['name'],
            'customer_id' => (int) $validated['customer_id'],
            'is_active' => $request->boolean('is_active', true),
            'view_customer' => $request->boolean('view_customer', true),
            'edit_customer' => $request->boolean('edit_customer', false),
            'view_directory' => $request->boolean('view_directory', false),
            'edit_directory' => $request->boolean('edit_directory', false),
            'view_orders' => $request->boolean('view_orders', false),
            'view_shipments' => $request->boolean('view_shipments', false),
            'view_shipment_costs' => $request->boolean('view_shipment_costs', false),
        ];
    }
}
