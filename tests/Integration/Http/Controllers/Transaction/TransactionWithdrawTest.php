<?php

namespace Http\Controllers\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserType;
use App\Exceptions\ValidationException;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class TransactionWithdrawTest extends AppTestCase
{
    use DatabaseAssertions;

    public function testWithdrawSuccessfully(): void
    {
        $this->postJson('/api/v1/users/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'type' => UserType::COMMON->value
        ]);
        $responseGetWallets = $this->get('/api/v1/wallets');
        $walletId = json_decode($responseGetWallets->getBody()->getContents(), true)['data'][0]['id'];

        $this->postJson("/api/v1/transactions/deposit/{$walletId}", [
           'amount' => 100.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 100.00,
        ]);
        $response = $this->postJson("/api/v1/transactions/withdraw/{$walletId}", [
            'amount' => 100.00,
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $walletId,
            'amount' => 100.00,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0.00,
        ]);
    }

    public function testWithdrawInsufficientFunds(): void
    {
        $this->postJson('/api/v1/users/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'type' => UserType::COMMON->value
        ]);
        $responseGetWallets = $this->get('/api/v1/wallets');
        $walletId = json_decode($responseGetWallets->getBody()->getContents(), true)['data'][0]['id'];

        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0.00,
        ]);
        $response = $this->postJson("/api/v1/transactions/withdraw/{$walletId}", [
            'amount' => 1.00,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'from_wallet_id' => $walletId,
            'amount' => 1.00,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0.00,
        ]);
    }

    public function testWithdrawInexistentWallet(): void
    {
        $response = $this->postJson("/api/v1/transactions/withdraw/1", [
            'amount' => 1.00,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'from_wallet_id' => 1,
            'amount' => 1.00,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
    }

}