<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = [
            'dashboard', 'customers', 'suppliers', 'orders', 'shipments',
            'finance', 'documents', 'tasks', 'employees', 'reports',
            'notifications', 'emails', 'settings', 'users', 'ai',
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'export'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}"]);
            }
        }

        Permission::where('name', 'like', 'patron.%')->delete();

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::whereNotIn('name', ['settings.edit', 'users.delete'])->get());

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions(Permission::where('name', 'like', '%.view')
            ->orWhere('name', 'like', '%.create')
            ->orWhere('name', 'like', '%.edit')
            ->get());

        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions(Permission::where('name', 'like', '%.view')
            ->orWhere('name', 'like', 'orders.%')
            ->orWhere('name', 'like', 'shipments.%')
            ->orWhere('name', 'like', 'customers.%')
            ->get());

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions(Permission::where('name', 'like', '%.view')->get());

        // Patron: inceleme (goruntuleme/rapor) + gorev atama + sevkiyat durumu
        $patron = Role::firstOrCreate(['name' => 'patron']);
        $patron->syncPermissions([
            'dashboard.view',
            'customers.view', 'customers.export',
            'suppliers.view', 'suppliers.export',
            'orders.view', 'orders.edit', 'orders.export',
            'shipments.view', 'shipments.edit', 'shipments.export',
            'finance.view', 'finance.export',
            'documents.view', 'documents.export',
            'tasks.view', 'tasks.create', 'tasks.edit',
            'reports.view', 'reports.export',
            'emails.view',
            'notifications.view',
        ]);
    }
}
