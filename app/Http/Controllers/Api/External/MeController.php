<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DirectoryContact;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke()
    {
        $ctx = external_api();

        return response()->json([
            'connection' => $ctx->connection->name,
            'customer' => $this->customerPayload($ctx->customer()),
            'permissions' => $ctx->connection->permissionsSummary(),
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
