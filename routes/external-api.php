<?php

use App\Http\Controllers\Api\External\CustomerController;
use App\Http\Controllers\Api\External\DirectoryController;
use App\Http\Controllers\Api\External\MeController;
use App\Http\Controllers\Api\External\OrderController;
use App\Http\Controllers\Api\External\ShipmentController;
use App\Http\Controllers\Api\External\ShipmentCostController;
use Illuminate\Support\Facades\Route;

Route::get('/me', MeController::class);
Route::get('/customer', [CustomerController::class, 'show']);
Route::patch('/customer', [CustomerController::class, 'update']);
Route::get('/directory', [DirectoryController::class, 'index']);
Route::post('/directory', [DirectoryController::class, 'store']);
Route::get('/directory/{contact}', [DirectoryController::class, 'show'])->whereNumber('contact');
Route::put('/directory/{contact}', [DirectoryController::class, 'update'])->whereNumber('contact');
Route::delete('/directory/{contact}', [DirectoryController::class, 'destroy'])->whereNumber('contact');
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::patch('/orders/{order}', [OrderController::class, 'update']);
Route::get('/shipments', [ShipmentController::class, 'index']);
Route::post('/shipments', [ShipmentController::class, 'store']);
Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
Route::patch('/shipments/{shipment}', [ShipmentController::class, 'update']);
Route::post('/shipments/{shipment}/costs', [ShipmentCostController::class, 'store']);
Route::patch('/shipment-costs/{cost}', [ShipmentCostController::class, 'update']);
Route::delete('/shipment-costs/{cost}', [ShipmentCostController::class, 'destroy']);
