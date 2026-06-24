<?php

namespace App\Core;

use App\Models\SystemModule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModuleManager
{
    public function __construct(protected HookManager $hooks) {}

    public function discover(): array
    {
        $modulesPath = base_path('modules');
        $discovered = [];

        if (!File::isDirectory($modulesPath)) {
            return $discovered;
        }

        foreach (File::directories($modulesPath) as $dir) {
            $manifestPath = $dir . '/module.json';
            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode($this->stripBom(File::get($manifestPath)), true);
            if (!$manifest || empty($manifest['slug'])) {
                continue;
            }

            $discovered[$manifest['slug']] = array_merge($manifest, [
                'path' => $dir,
            ]);
        }

        return $discovered;
    }

    public function syncDiscovered(): void
    {
        foreach ($this->discover() as $slug => $manifest) {
            SystemModule::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $manifest['name'] ?? $slug,
                    'version' => $manifest['version'] ?? '1.0.0',
                    'description' => $manifest['description'] ?? null,
                    'provider_class' => $manifest['provider'] ?? null,
                    'path' => $manifest['path'],
                    'manifest' => $manifest,
                ]
            );
        }
    }

    public function bootEnabled(): void
    {
        $modules = SystemModule::where('is_enabled', true)->get();

        foreach ($modules as $module) {
            $this->bootModule($module);
        }

        $this->hooks->fire('modules.booted');
    }

    protected function bootModule(SystemModule $module): void
    {
        $providerClass = $module->provider_class;

        if ($providerClass && class_exists($providerClass)) {
            $instance = app($providerClass);

            if (method_exists($instance, 'boot')) {
                $instance->boot();
            }

            if (method_exists($instance, 'registerRoutes')) {
                $instance->registerRoutes();
            }

            if (method_exists($instance, 'registerPermissions')) {
                $this->syncPermissions($module);
            }
        }

        $routesFile = $module->path . '/Routes/web.php';
        if ($module->path && File::exists($routesFile)) {
            Route::middleware(['web', 'auth'])->group($routesFile);
        }

        $viewsPath = $module->path . '/Resources/views';
        if ($module->path && File::isDirectory($viewsPath)) {
            View::addNamespace('module.' . $module->slug, $viewsPath);
        }

        $this->hooks->fire('module.booted', $module);
    }

    public function enable(SystemModule $module): void
    {
        $this->syncPermissions($module);
        $module->update(['is_enabled' => true, 'installed_at' => $module->installed_at ?? now()]);
        cache()->forget('system.menu.items');
    }

    public function syncPermissions(SystemModule $module): void
    {
        $permissions = $module->manifest['permissions'] ?? [];

        if (empty($permissions) && $module->provider_class && class_exists($module->provider_class)) {
            $instance = app($module->provider_class);
            if (method_exists($instance, 'registerPermissions')) {
                $permissions = $instance->registerPermissions();
            }
        }

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        if ($role = Role::where('name', 'super-admin')->first()) {
            $role->givePermissionTo($permissions);
        }
    }

    public function disable(SystemModule $module): void
    {
        if ($module->is_core) {
            throw new \RuntimeException(__('extensions.core_module_cannot_disable'));
        }
        $module->update(['is_enabled' => false]);
        cache()->forget('system.menu.items');
    }

    public function getMenuItems(): array
    {
        return cache()->remember('system.menu.items', 3600, function () {
            $items = [];

            $modules = SystemModule::where('is_enabled', true)->get();
            foreach ($modules as $module) {
                $menu = $module->manifest['menu'] ?? [];
                foreach ($menu as $item) {
                    if (!empty($item['permission']) && !can_access($item['permission'])) {
                        continue;
                    }
                    $items[] = array_merge($item, ['module' => $module->slug]);
                }
            }

            return $this->hooks->filter('menu.items', $items);
        });
    }

    protected function stripBom(string $content): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    }
}
