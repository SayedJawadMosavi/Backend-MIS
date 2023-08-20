<?php

namespace Database\Factories;

use Faker\Core\Number;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            "phone_number" => fake()->phoneNumber(),
            "profile" => fake()->name(),
            "current_address" => fake()->address(),
            "permenent_address" => fake()->address(),
            "job_title" => fake()->name(),
            "employee_id_number" => fake()->randomFloat(3),
            "employment_start_date" => now(),
            "employment_end_date" => now()


        ];
    }
}
