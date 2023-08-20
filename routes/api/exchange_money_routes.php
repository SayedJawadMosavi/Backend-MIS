<?php

use App\Http\Controllers\ExchangeMoneyController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/exchange-money', ExchangeMoneyController::class);
Route::put('/edit-exchanges', [ExchangeMoneyController::class, 'editExchange']);
Route::post('/restore_exchange-money/{id}', [ExchangeMoneyController::class, 'restore']);
Route::delete('/force-delete-exchange-money/{id}', [ExchangeMoneyController::class, 'forceDelete']);
