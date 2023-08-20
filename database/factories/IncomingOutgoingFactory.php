<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncomingOutgoing>
 */
class IncomingOutgoingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $user = User::get()->first();

        return [
            'name' => fake()->name(),
            "type" => fake()->randomElement(["incoming", "outgoing"]),
            "amount" => fake()->randomFloat(1),
            'created_by' => $user->id,
        ];
    }
}
