<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Support\Money\Enums\Currency;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1.00, 5.00);
        $quantity = fake()->randomFloat(2, 1.00, 100.00);

        return [
            'cost' => Money::parse($price * $quantity),
            'price' => Money::parse($price),
            'quantity' => $quantity,
        ];
    }
}

