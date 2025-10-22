<?php

namespace Http\Controllers\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserType;
use App\Exceptions\ValidationException;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class TransactionDepositTest extends AppTestCase
{
    use DatabaseAssertions;

    public function testDepositSuccessfully(): void
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
        $response = $this->postJson("/api/v1/transactions/deposit/{$walletId}", [
            'amount' => 100.00,
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('transactions', [
            'to_wallet_id' => $walletId,
            'amount' => 100.00,
            'type' => TransactionType::DEPOSIT->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 100.00,
        ]);
    }

    public function testDepositWithInexistentWallet(): void
    {
        $response = $this->postJson("/api/v1/transactions/deposit/1", [
            'amount' => 100.00,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'to_wallet_id' => 1,
            'amount' => 100.00,
            'type' => TransactionType::DEPOSIT->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseMissing('wallets', [
            'id' => 1,
            'balance' => 100.00,
        ]);
    }

    public function testDepositWithInvalidAmount(): void
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
        $response = $this->postJson("/api/v1/transactions/deposit/{$walletId}", [
            'amount' => -1.00
        ]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'to_wallet_id' => $walletId,
            'amount' => -1.00,
            'type' => TransactionType::DEPOSIT->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseMissing('wallets', [
            'id' => $walletId,
            'balance' => -1.00,
        ]);
    }

}
