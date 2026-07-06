<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    public function show()
    {
        abort_unless(external_api()->allows('customer'), 403);

        $customer = external_api()->customer();

        return response()->json([
            'data' => [
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
            ],
        ]);
    }
}
