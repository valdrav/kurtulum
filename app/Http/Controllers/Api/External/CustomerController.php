<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function show()
    {
        abort_unless(external_api()->allows('customer'), 403);

        $customer = external_api()->customer();

        return response()->json([
            'data' => $this->payload($customer),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(external_api()->allows('edit_customer'), 403);

        $customer = external_api()->customer();

        $validated = $request->validate([
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:2000',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Updated.',
            'data' => $this->payload($customer->fresh()),
        ]);
    }

    protected function payload($customer): array
    {
        return [
            'id' => $customer->uuid,
            'company_name' => $customer->company_name,
            'contact_person' => $customer->contact_person,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'country' => $customer->country,
            'city' => $customer->city,
            'address' => $customer->address,
            'tax_number' => $customer->tax_number,
            'type' => $customer->type,
            'status' => $customer->status,
            'currency' => $customer->currency,
        ];
    }
}
