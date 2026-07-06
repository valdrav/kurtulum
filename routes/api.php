<?php

use App\Http\Controllers\Api\External\CustomerController;
use App\Http\Controllers\Api\External\DirectoryController;
use App\Http\Controllers\Api\External\MeController;
use App\Http\Controllers\Api\External\OrderController;
use App\Http\Controllers\Api\External\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('external.api')->group(function () {
    Route::get('/me', MeController::class);
    Route::get('/customer', [CustomerController::class, 'show']);
    Route::patch('/customer', [CustomerController::class, 'update']);
    Route::get('/directory', [DirectoryController::class, 'index']);
    Route::post('/directory', [DirectoryController::class, 'store']);
    Route::get('/directory/{contact}', [DirectoryController::class, 'show']);
    Route::put('/directory/{contact}', [DirectoryController::class, 'update']);
    Route::delete('/directory/{contact}', [DirectoryController::class, 'destroy']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
});
