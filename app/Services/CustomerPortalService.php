<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPortalAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerPortalService
{
    public function upsert(Customer $customer, array $data, int $actorId): CustomerPortalAccess
    {
        return DB::transaction(function () use ($customer, $data, $actorId) {
            $access = CustomerPortalAccess::firstOrNew(['customer_id' => $customer->id]);
            $access->fill([
                'is_active' => (bool) ($data['is_active'] ?? false),
                'view_orders' => (bool) ($data['view_orders'] ?? true),
                'view_shipments' => (bool) ($data['view_shipments'] ?? true),
                'view_shipment_costs' => (bool) ($data['view_shipment_costs'] ?? true),
                'view_directory' => (bool) ($data['view_directory'] ?? false),
                'edit_profile' => (bool) ($data['edit_profile'] ?? true),
                'created_by' => $access->exists ? $access->created_by : $actorId,
            ]);

            $user = $access->user;

            if ($access->is_active) {
                if (! $user) {
                    $user = User::create([
                        'name' => $data['name'] ?? $customer->company_name,
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                        'user_type' => 'portal',
                        'customer_id' => $customer->id,
                        'locale' => 'tr',
                        'theme' => 'light',
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]);

                    $role = Role::firstOrCreate(['name' => 'portal-customer']);
                    $user->syncRoles([$role->name]);
                } else {
                    $user->update([
                        'name' => $data['name'] ?? $user->name,
                        'email' => $data['email'] ?? $user->email,
                        'customer_id' => $customer->id,
                        'user_type' => 'portal',
                        'is_active' => true,
                        'department_id' => null,
                    ]);

                    $role = Role::firstOrCreate(['name' => 'portal-customer']);
                    $user->syncRoles([$role->name]);

                    if (! empty($data['password'])) {
                        $user->update(['password' => Hash::make($data['password'])]);
                    }
                }

                $access->user_id = $user->id;
            } elseif ($user) {
                $user->update(['is_active' => false]);
            }

            $access->save();

            return $access->fresh(['user', 'customer']);
        });
    }

    public function revoke(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $access = $customer->portalAccess;

            if (! $access) {
                return;
            }

            $access->update(['is_active' => false]);
            $access->user?->update(['is_active' => false]);
        });
    }
}
