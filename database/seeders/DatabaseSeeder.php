<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Car;
use App\Models\Employee;
use App\Models\ExchangeMoney;
use App\Models\User;
use App\Models\IncomingOutgoing;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $permissions = [
            'user_view', 'user_create', 'user_delete', 'user_restore',
            'employee_view', 'employee_create', 'employee_delete', 'employee_restore', 'exchange_view',
            'exchange_create', 'exchange_delete', 'exchange_restore',
            'order_view', 'order_create', 'order_delete', 'order_restore',
            'car_view', 'car_create', 'car_delete', 'car_restore',
            'income_view', 'income_create', 'income_delete', 'income_restore',
            'salary_view', 'salary_create', 'salary_delete', 'salary_restore'
        ];
        User::create(
            [
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'email_verified_at' => now(),
                "role" => 'admin',
                'permissions' => json_encode($permissions),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => '12',
            ]
        );
        // Employee::factory(10)->create();
        // IncomingOutgoing::factory(10)->create();
    }
}
