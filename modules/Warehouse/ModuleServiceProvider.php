<?php

namespace Modules\Warehouse;

use Illuminate\Support\Facades\Route;

class ModuleServiceProvider
{
    public function boot(): void
    {
        hook()->register('shipment.created', function ($shipment) {
            return $shipment;
        });
    }

    public function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'permission:warehouse.view'])
            ->prefix('warehouse')
            ->name('warehouse.')
            ->group(function () {
                Route::get('/', [Http\Controllers\WarehouseController::class, 'index'])->name('index');
            });
    }

    public function registerPermissions(): array
    {
        return ['warehouse.view', 'warehouse.create', 'warehouse.edit'];
    }
}
