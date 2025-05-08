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

    public function transfer(User $sender, User $recipient, int $amount): int
    {
        try {
            DB::beginTransaction();

            $sender->decrement('balance', $amount);
            $recipient->increment('balance', $amount);

            DB::commit();

            return $sender->balance;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deposit(User $user, int $amount): int
    {
        try {
            DB::beginTransaction();
            $user->balance += $amount;
            $user->save();

            DB::commit();

            return $user->balance;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $user->balance;
    }
}
