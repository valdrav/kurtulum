<?php

namespace Modules\QualityCheck;

use Illuminate\Support\Facades\Route;

class ModuleServiceProvider
{
    public function boot(): void {}

    public function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'permission:quality-check.view'])
            ->prefix('quality-check')
            ->name('quality-check.')
            ->group(function () {
                Route::get('/', [Http\Controllers\QualityCheckController::class, 'index'])->name('index');
            });
    }

    public function registerPermissions(): array
    {
        return ['quality-check.view', 'quality-check.create', 'quality-check.edit'];
    }
}
