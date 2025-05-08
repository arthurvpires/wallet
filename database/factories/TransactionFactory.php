<?php

namespace Database\Factories;

use App\Models\User;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([TransactionType::DEPOSIT->value, TransactionType::TRANSFER->value, TransactionType::RECEIVED_TRANSFER->value]),
            'amount' => $this->faker->randomNumber(1, 1000),
            'recipient_id' => User::factory(),
            'was_reverted' => false,
        ];
    }
}
