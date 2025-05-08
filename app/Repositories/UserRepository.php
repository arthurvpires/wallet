<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function transfer(User $sender, User $recipient, float $amount): float
    {
        try {
            DB::beginTransaction();

            $amountInCents = (int) round($amount * 100);
            $sender->decrement('balance', $amountInCents);
            $recipient->increment('balance', $amountInCents);

            DB::commit();

            return $sender->balance / 100;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deposit(User $user, float $amount): float
    {
        try {
            DB::beginTransaction();
            $amountInCents = (int) round($amount * 100);
            $user->balance += $amountInCents;
            $user->save();

            DB::commit();

            return $user->balance / 100;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
