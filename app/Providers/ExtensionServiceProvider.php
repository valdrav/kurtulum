<?php

namespace App\Providers;

use App\Core\HookManager;
use App\Core\ModuleManager;
use App\Core\PaymentMethodService;
use App\Core\Registry;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookManager::class);
        $this->app->singleton(Registry::class);
        $this->app->singleton(PaymentMethodService::class);
        $this->app->singleton(ModuleManager::class);
    }

    public function boot(): void
    {
        if (!filter_var(config('ticari.installed'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (!Schema::hasTable('system_modules')) {
            return;
        }

        try {
            app(ModuleManager::class)->bootEnabled();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
