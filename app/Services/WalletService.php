<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Exceptions\RevertException;
use App\Exceptions\DepositException;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\TransferException;
use App\Repositories\TransactionRepository;

class WalletService
{
    protected UserRepository $userRepo;
    protected TransactionRepository $transactionRepo;
    protected User $authUser;

    public function __construct(UserRepository $userRepo, TransactionRepository $transactionRepo)
    {
        $this->userRepo = $userRepo;
        $this->transactionRepo = $transactionRepo;
        $this->authUser = Auth::user();
    }

    public function deposit(float $amount): float
    {
        if ($amount <= 0) {
            throw new DepositException('O valor do depósito deve ser maior que zero.');
        }

        $deposit = $this->userRepo->deposit($this->authUser, $amount);
        $this->createTransaction($this->authUser->id, Transaction::TYPE_DEPOSIT, $amount, null);

        return $deposit;
    }

    public function transfer(string $recipientEmail, float $amount): float
    {
        if ($amount <= 0) {
            throw new TransferException('O valor da transferência deve ser maior que zero.');
        }

        if ($this->authUser->email === $recipientEmail) {
            throw new TransferException('Você não pode transferir para si mesmo.');
        }

        if ($this->authUser->balance < $amount) {
            throw new TransferException('Saldo insuficiente para transferência.');
        }

        $recipient = $this->userRepo->findByEmail($recipientEmail);

        if (!$recipient) {
            throw new TransferException('Destinatário não encontrado.');
        }

        $transfer = $this->userRepo->transfer($this->authUser, $recipient, $amount);
        $this->createTransaction($this->authUser->id, Transaction::TYPE_TRANSFER, $amount, $recipient->id);

        return $transfer;
    }

    public function revert(int $transactionId): float
    {
        $transaction = $this->transactionRepo->findById($transactionId);
        $recipient = $this->userRepo->findById($transaction->user_id);

        if (!$transaction) {
            throw new RevertException('Transação não encontrada.');
        }

        if ($transaction->was_reverted) {
            throw new RevertException('Esta transação já foi revertida.');
        }

        if ($recipient->balance < $transaction->amount) {
            throw new RevertException('Saldo insuficiente para reverter a transação.');
        }

        return $this->transactionRepo->revert($transaction);
    }

    public function history(): array
    {
        return $this->transactionRepo->getTransactionHistory($this->authUser);
    }

    private function createTransaction(int $userId, string $type, float $amount, ?int $recipientId): Transaction
    {
        $baseTransaction = [
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'recipient_id' => $recipientId,
        ];

        if ($type === Transaction::TYPE_TRANSFER) {
            $this->transactionRepo->create($baseTransaction);

            //Create transaction for recipient
            return $this->transactionRepo->create([
                'user_id' => $recipientId,
                'type' => Transaction::TYPE_RECEIVED_TRANSFER, 
                'amount' => $amount,
                'recipient_id' => $userId, 
            ]);
        }

        return $this->transactionRepo->create($baseTransaction);
    }
}
