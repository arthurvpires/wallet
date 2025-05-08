<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;


class TransactionFactory extends Factory
{

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([Transaction::TYPE_DEPOSIT, Transaction::TYPE_TRANSFER, Transaction::TYPE_RECEIVED_TRANSFER]),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'recipient_id' => User::factory(),
            'was_reverted' => false,
        ];
    }
}
