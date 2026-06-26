<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class CrmDeletionService
{
    public function customerDeletionBlockReason(Customer $customer): ?string
    {
        if ($customer->orders()->whereNotIn('status', ['cancelled'])->exists()) {
            return __('customers.cannot_delete_has_orders');
        }

        if ($customer->shipments()->whereNotIn('status', ['cancelled', 'completed', 'delivered'])->exists()) {
            return __('customers.cannot_delete_has_shipments');
        }

        if ($this->accountHasBalance($customer->account)) {
            return __('customers.cannot_delete_has_balance');
        }

        return null;
    }

    public function supplierDeletionBlockReason(Supplier $supplier): ?string
    {
        if ($supplier->orders()->whereNotIn('status', ['cancelled'])->exists()) {
            return __('suppliers.cannot_delete_has_orders');
        }

        if ($this->accountHasBalance($supplier->account)) {
            return __('suppliers.cannot_delete_has_balance');
        }

        return null;
    }

    public function deleteCustomer(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $customer->load('account');
            $customer->account?->delete();
            $customer->contacts()->delete();
            $customer->delete();
        });
    }

    public function deleteSupplier(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            $supplier->load('account');
            $supplier->account?->delete();
            $supplier->contacts()->delete();
            $supplier->delete();
        });
    }

    protected function accountHasBalance(?\App\Models\Account $account): bool
    {
        return $account && abs((float) $account->current_balance) > 0.01;
    }
}
