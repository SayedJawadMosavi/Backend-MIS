<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/orders', OrderController::class);
Route::post('/order-item', [OrderController::class, 'addItem']);
Route::put('/order-item', [OrderController::class, 'updateItem']);
Route::post('/order-expense', [OrderController::class, 'addExpense']);
Route::put('/order-expense', [OrderController::class, 'updateExpense']);
Route::post('/order-payment', [OrderController::class, 'addPayment']);
Route::put('/order-payment', [OrderController::class, 'updatePayment']);
Route::delete('/order-payment/{id}', [OrderController::class, 'deletePayment']);
Route::post('/restore/{type}/{id}', [OrderController::class, 'restore']);
Route::delete('/delete/{type}/{id}', [OrderController::class, 'destroy']);
Route::delete('/force-delete/{type}/{id}', [OrderController::class, 'forceDelete']);
