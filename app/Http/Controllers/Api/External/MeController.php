<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class MeController extends Controller
{
    public function __invoke()
    {
        $ctx = external_api();

        return response()->json([
            'connection' => $ctx->connection->name,
            'customer' => $this->customerPayload($ctx->customer()),
            'permissions' => $ctx->connection->permissionsSummary(),
            'stats' => [
                'orders' => $ctx->allows('orders') ? $ctx->ordersQuery()->count() : 0,
                'shipments' => $ctx->allows('shipments') ? $ctx->shipmentsQuery()->count() : 0,
            ],
        ]);
    }

    protected function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->uuid,
            'company_name' => $customer->company_name,
            'contact_person' => $customer->contact_person,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'status' => $customer->status,
        ];
    }
}
