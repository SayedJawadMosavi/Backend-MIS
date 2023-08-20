<?php

use App\Http\Controllers\CarController;
use App\Http\Controllers\CarExpenseController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/cars', CarController::class);
Route::get('current-car', [CarController::class, 'getCurrentCar']);
Route::post('car-status', [CarController::class, 'changeStatus']);
Route::apiResource('/car-expenses', CarExpenseController::class);
Route::post('restore-car-expenses/{id}', [CarExpenseController::class, 'restore']);
Route::delete('force-delete-car-expenses/{id}', [CarExpenseController::class, 'forceDelete']);

Route::get('car-orders', [CarController::class, 'carOrders']);
