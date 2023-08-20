<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalaryPaymentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('employees', EmployeeController::class);

Route::post('restore-employees/{id}', [EmployeeController::class, 'restore']);
Route::delete('force-delete-employees/{id}', [EmployeeController::class, 'forceDelete']);
Route::get('employee-list', [EmployeeController::class, 'getEmployees']);
Route::apiResource('salary-payments', SalaryPaymentController::class);
Route::post('restore/salary-payments/{id}', [SalaryPaymentController::class, 'restore']);
Route::delete('force-delete-salary-payments/{id}', [SalaryPaymentController::class, 'forceDelete']);
