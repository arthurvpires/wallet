<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'balance' => 1000
        ]);

        $this->recipient = User::factory()->create([
            'balance' => 500
        ]);
    }

    public function test_deposit_endpoint()
    {
        $amount = 1.50;
        $amountInCents = (int) round($amount * 100);

        $response = $this->actingAs($this->user)
            ->postJson('/wallet/deposit', [
                'amount' => $amount
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'balance' => 1000 + $amountInCents
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => TransactionType::DEPOSIT->value,
            'amount' => $amountInCents
        ]);
    }


    public function test_deposit_with_invalid_amount()
    {
        $response = $this->actingAs($this->user)
            ->postJson('wallet/deposit', [
                'amount' => -100
            ]);

        $response->assertStatus(422);
    }

    public function test_transfer_endpoint()
    {
        $amount = 1.00;
        $amountInCents = (int) round($amount * 100);

        $response = $this->actingAs($this->user)
            ->postJson('/wallet/transfer', [
                'recipient' => $this->recipient->email,
                'amount' => $amount
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'balance' => 1000 - $amountInCents
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->recipient->id,
            'balance' => 500 + $amountInCents
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => TransactionType::TRANSFER->value,
            'amount' => $amountInCents,
            'recipient_id' => $this->recipient->id
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->recipient->id,
            'type' => TransactionType::RECEIVED_TRANSFER->value,
            'amount' => $amountInCents
        ]);
    }


    public function test_transfer_with_insufficient_balance()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/wallet/transfer', [
                'recipient' => $this->recipient->email,
                'amount' => 2000
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Saldo insuficiente para transferência.'
            ]);
    }

    public function test_transfer_to_nonexistent_user()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/wallet/transfer', [
                'recipient' => 'nonexistent@example.com',
                'amount' => 100
            ]);

        $response->assertStatus(422);
    }

    public function test_history_endpoint()
    {
        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => TransactionType::DEPOSIT->value,
            'amount' => 100
        ]);

        Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => TransactionType::TRANSFER->value,
            'amount' => 50,
            'recipient_id' => $this->recipient->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/wallet/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                'id',
                'user_id',
                'type',
                'amount',
                'recipient_email',
                'created_at',
                'was_reverted'
            ]
        ]);
    }

    public function test_revert_transaction()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => TransactionType::TRANSFER->value,
            'amount' => 100,
            'recipient_id' => $this->recipient->id
        ]);

        $this->user->update(['balance' => 900]);
        $this->recipient->update(['balance' => 600]);

        $response = $this->actingAs($this->user)
            ->postJson('/wallet/revert', [
                'transaction_id' => $transaction->id
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'balance' => 1000
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->recipient->id,
            'balance' => 500
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'was_reverted' => true
        ]);
    }

    public function test_transaction_already_reverted()
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'type' => TransactionType::TRANSFER->value,
            'amount' => 100,
            'recipient_id' => $this->recipient->id,
            'was_reverted' => true
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/wallet/revert', [
                'transaction_id' => $transaction->id,
                'message' => 'Esta transação já foi revertida.'
            ]);

        $response->assertStatus(500);
    }

    public function test_revert_nonexistent_transaction()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/wallet/revert', [
                'transaction_id' => 999
            ]);

        $response->assertStatus(422);
    }
}
