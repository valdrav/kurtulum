<?php

use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DirectoryController;
use App\Http\Controllers\Portal\OrderController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');

Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
