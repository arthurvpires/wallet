<?php

namespace App\Repositories;

use Exception;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function create(array $fields): Transaction
    {
        if (isset($fields['amount'])) {
            $fields['amount'] = (int) round($fields['amount'] * 100);
        }

        return Transaction::create($fields);
    }

    public function getTransactionHistory(User $user): array
    {
        return Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($transaction) => [
                'id' => $transaction->id,
                'user_id' => $transaction->id,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount / 100,
                'recipient_email' => $transaction->recipient?->email ?? '' ,
                'created_at' => $transaction->created_at,
                'was_reverted' => $transaction->was_reverted,
            ])
            ->toArray();
    }

    public function findById(int $id): Transaction
    {
        return Transaction::find($id);
    }

    public function revert(Transaction $transaction): float
    {
        $sender = $transaction->user;
        $recipient = $transaction->recipient;
        $amount = $transaction->amount;

        try {
            DB::beginTransaction();
            if ($transaction->type->value === TransactionType::DEPOSIT->value) {
                $sender->decrement('balance', $amount);
            } else {
                $sender->increment('balance', $amount);
                $recipient->decrement('balance', $amount);
                $this->findReceivedTransfer($recipient, $amount, $transaction->created_at);
            }

            $transaction->was_reverted = true;
            $transaction->save();

            DB::commit();

            return $sender->balance / 100;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findReceivedTransfer(User $recipient, int $amount, string $createdAt): ?Transaction
    {
        $recipientTransaction = Transaction::where('user_id', $recipient->id)
            ->where('type', TransactionType::RECEIVED_TRANSFER->value)
            ->where('amount', $amount)
            ->where('created_at', $createdAt)
            ->first();

        if ($recipientTransaction) {
            $recipientTransaction->was_reverted = true;
            $recipientTransaction->save();
        }

        return $recipientTransaction;
    }
}
