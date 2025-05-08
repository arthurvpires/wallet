<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Services\WalletService;
use App\Repositories\UserRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Auth;
use Mockery;

class WalletServiceTest extends TestCase
{
    protected WalletService $walletService;
    protected UserRepository $userRepo;
    protected TransactionRepository $transactionRepo;
    protected User $authUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->transactionRepo = Mockery::mock(TransactionRepository::class);
        $this->authUser = Mockery::mock(User::class);
        
        Auth::shouldReceive('user')->andReturn($this->authUser);
        
        $this->walletService = new WalletService(
            $this->userRepo,
            $this->transactionRepo
        );
    }

    public function test_deposit_with_valid_amount()
    {
        $amount = 100.00;
        $this->authUser->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        $this->transactionRepo->shouldReceive('create')
            ->once()
            ->andReturn(new Transaction());
            
        $this->userRepo->shouldReceive(Transaction::TYPE_DEPOSIT)
            ->once()
            ->with($this->authUser, $amount)
            ->andReturn($amount);

        $result = $this->walletService->deposit($amount);
        $this->assertEquals($amount, $result);
    }

    public function test_deposit_with_invalid_amount()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('O valor do depósito deve ser maior que zero.');
        
        $this->walletService->deposit(0);
    }

    public function test_transfer_with_valid_data()
    {
        $amount = 100.00;
        $recipientEmail = 'recipient@example.com';
        $recipient = Mockery::mock(User::class);
        
        $this->authUser->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('sender@example.com');
        $this->authUser->shouldReceive('getAttribute')
            ->with('balance')
            ->andReturn(200.00);
        $this->authUser->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
            
        $recipient->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(2);
            
        $this->userRepo->shouldReceive('findByEmail')
            ->with($recipientEmail)
            ->andReturn($recipient);
            
        $this->transactionRepo->shouldReceive('create')
            ->twice()
            ->andReturn(new Transaction());
            
        $this->userRepo->shouldReceive(Transaction::TYPE_TRANSFER)
            ->once()
            ->with($this->authUser, $recipient, $amount)
            ->andReturn($amount);

        $result = $this->walletService->transfer($recipientEmail, $amount);
        $this->assertEquals($amount, $result);
    }

    public function test_transfer_with_insufficient_balance()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Saldo insuficiente para transferência.');
        
        $this->authUser->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('sender@example.com');
        $this->authUser->shouldReceive('getAttribute')
            ->with('balance')
            ->andReturn(50.00);
            
        $this->walletService->transfer('recipient@example.com', 100.00);
    }

    public function test_transfer_to_self()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Você não pode transferir para si mesmo.');
        
        $this->authUser->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('user@example.com');
            
        $this->walletService->transfer('user@example.com', 100.00);
    }

    public function test_show_transactions_history()
    {
        $expectedHistory = ['transaction1', 'transaction2'];
        
        $this->transactionRepo->shouldReceive('getTransactionHistory')
            ->once()
            ->with($this->authUser)
            ->andReturn($expectedHistory);

        $result = $this->walletService->history();
        $this->assertEquals($expectedHistory, $result);
    }

    public function test_revert_transaction()
    {
        $transactionId = 1;
        $transaction = Mockery::mock(Transaction::class);
        $user = Mockery::mock(User::class);
        
        $this->transactionRepo->shouldReceive('findById')
            ->with($transactionId)
            ->andReturn($transaction);
            
        $transaction->shouldReceive('getAttribute')
            ->with('was_reverted')
            ->andReturn(false);
        $transaction->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);
        $transaction->shouldReceive('getAttribute')
            ->with('amount')
            ->andReturn(100.00);
            
        $user->shouldReceive('getAttribute')
            ->with('balance')
            ->andReturn(200.00);
            
        $this->userRepo->shouldReceive('findById')
            ->with(1)
            ->andReturn($user);
            
        $this->transactionRepo->shouldReceive('revert')
            ->once()
            ->with($transaction)
            ->andReturn(100.00);

        $result = $this->walletService->revert($transactionId);
        $this->assertEquals(100.00, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 