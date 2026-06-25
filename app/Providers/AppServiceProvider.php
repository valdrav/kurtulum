<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        if (app()->environment('local') && !app()->runningInConsole()) {
            $root = request()->getSchemeAndHttpHost() . request()->getBaseUrl();
            \Illuminate\Support\Facades\URL::forceRootUrl(rtrim($root, '/'));
        }

        if (!filter_var(config('ticari.installed'), FILTER_VALIDATE_BOOLEAN)) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);

            return;
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('system_languages')) {
            return;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            try {
                $sessionLifetime = \App\Models\Setting::get('session_lifetime');
                if ($sessionLifetime && is_numeric($sessionLifetime)) {
                    config(['session.lifetime' => (int) $sessionLifetime]);
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            view()->composer('*', function ($view) {
                $view->with('registryLanguages', registry()->languages());
                $view->with('registryCurrencies', registry()->currencies());
                $view->with('moduleMenuItems', modules()->getMenuItems());

                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $view->with('companySettings', [
                        'name' => \App\Models\Setting::get('company_name', config('app.name')),
                        'email' => \App\Models\Setting::get('company_email'),
                        'phone' => \App\Models\Setting::get('company_phone'),
                    ]);
                }
            });

            if (! $this->app->runningInConsole()) {
                $this->app->terminating(function () {
                    try {
                        if (\Illuminate\Support\Facades\Schema::hasTable('system_currencies')) {
                            app(\App\Services\ExchangeRateService::class)->sync();
                        }
                    } catch (\Throwable) {
                        // Kur senkronu sessizce atlanır
                    }
                });
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
