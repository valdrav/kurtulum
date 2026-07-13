<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncDocumentPermissionsCommand extends Command
{
    protected $signature = 'documents:sync-permissions';

    protected $description = 'Ensure evrak (documents) permissions exist and are assigned to staff roles';

    public function handle(): int
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view', 'create', 'edit', 'delete', 'export'] as $action) {
            Permission::firstOrCreate(['name' => "documents.{$action}"]);
        }

        $view = Permission::findByName('documents.view');
        $create = Permission::findByName('documents.create');
        $delete = Permission::findByName('documents.delete');

        foreach (['super-admin', 'admin', 'manager', 'operator', 'viewer', 'patron'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $role->givePermissionTo($view);

            if (in_array($roleName, ['manager', 'admin', 'super-admin', 'patron'], true)) {
                $role->givePermissionTo($create);
            }

            if (in_array($roleName, ['admin', 'super-admin', 'patron'], true)) {
                $role->givePermissionTo($delete);
            }
        }

        $this->info('Documents permissions synced for patron, admin, manager, operator, viewer.');

        return self::SUCCESS;
    }
}
