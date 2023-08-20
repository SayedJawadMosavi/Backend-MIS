<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeMoney>
 */
class ExchangeMoneyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "sender_name"=>fake()->name(),
            "amount"=>fake()->randomFloat(min:1000,max:10000),
            "province"=>fake()->name(),
            "currency"=>fake()->currencyCode(),
            "receiver_name"=>fake()->name(),
            "receiver_father_name"=>fake()->name(),
            "phone_number"=>fake()->phoneNumber(),
            "receiver_id_no"=>fake()->randomDigit(),
            "date"=>fake()->date(),
        ];
    }
}
