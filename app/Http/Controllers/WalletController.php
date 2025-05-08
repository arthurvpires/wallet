<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\RevertRequest;
use App\Http\Requests\TransferRequest;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function deposit(DepositRequest $request): int
    {
        return $this->walletService->deposit($request->amount());
    }

    public function transfer(TransferRequest $request): int
    {
        return $this->walletService->transfer($request->recipient(), $request->amount());
    }

    public function revert(RevertRequest $request): int
    {
        return $this->walletService->revert($request->transactionId());
    }

    public function history(): array
    {
        return $this->walletService->history();
    }
}
