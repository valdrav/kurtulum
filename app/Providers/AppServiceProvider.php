<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $logsDir = storage_path('logs');
        if (! is_dir($logsDir)) {
            @mkdir($logsDir, 0775, true);
        }
        if (! is_writable($logsDir)) {
            config(['logging.default' => 'errorlog']);
        }
    }

    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        if ($this->app->environment('production') && ! $this->app->runningInConsole()) {
            URL::forceScheme('https');
        }

        if (app()->environment('local') && ! app()->runningInConsole()) {
            $root = request()->getSchemeAndHttpHost() . request()->getBaseUrl();
            URL::forceRootUrl(rtrim($root, '/'));
        }

        if (! filter_var(config('ticari.installed'), FILTER_VALIDATE_BOOLEAN)) {
            config([
                'session.driver' => 'file',
                'session.domain' => null,
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);

            return;
        }

        if (! Schema::hasTable('system_languages')) {
            return;
        }

        if (Schema::hasTable('settings')) {
            try {
                $sessionLifetime = \App\Models\Setting::get('session_lifetime');
                if ($sessionLifetime && is_numeric($sessionLifetime)) {
                    config(['session.lifetime' => (int) $sessionLifetime]);
                }
            } catch (\Throwable) {
                //
            }
        }

        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            $shared = [
                'registryLanguages' => registry()->languages(),
                'registryCurrencies' => registry()->currencies(),
                'moduleMenuItems' => modules()->getMenuItems(),
            ];

            if (Schema::hasTable('settings')) {
                $shared['companySettings'] = [
                    'name' => \App\Models\Setting::get('company_name', config('app.name')),
                    'email' => \App\Models\Setting::get('company_email'),
                    'phone' => \App\Models\Setting::get('company_phone'),
                ];
            }

            View::share($shared);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
