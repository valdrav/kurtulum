<?php

namespace Modules\Insurance;

use Illuminate\Support\Facades\Route;

class ModuleServiceProvider
{
    public function boot(): void
    {
        hook()->register('payment.before_create', function ($data) {
            // Örnek: Sigorta modülü ödeme oluşturmadan önce veriyi zenginleştirir
            return $data;
        });
    }

    public function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'permission:insurance.view'])->prefix('insurance')->name('insurance.')->group(function () {
            Route::get('/', [Http\Controllers\InsuranceController::class, 'index'])->name('index');
        });
    }

    public function registerPermissions(): array
    {
        return ['insurance.view', 'insurance.create', 'insurance.edit'];
    }

    public function registerMenuItems(): array
    {
        return [];
    }
}
