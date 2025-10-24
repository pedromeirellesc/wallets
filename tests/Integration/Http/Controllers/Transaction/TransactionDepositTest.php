<?php

namespace Http\Controllers\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Tests\AppTestCase;
use Tests\Traits\CreatesUsers;
use Tests\Traits\DatabaseAssertions;
use Tests\Traits\MakesTransactions;

class TransactionDepositTest extends AppTestCase
{
    use DatabaseAssertions;
    use CreatesUsers;
    use MakesTransactions;

    public function testDepositSuccessfully(): void
    {
        $walletId = $this->createUserWithWallet();

        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 0]);

        $result = $this->makeDeposit($walletId, 100.00);

        $this->assertEquals(201, $result['response']->getStatusCode());
        $this->assertDatabaseHas('transactions', [
            'to_wallet_id' => $walletId,
            'amount' => 10000,
            'type' => TransactionType::DEPOSIT->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 10000]);
    }

    public function testDepositWithNonExistentWallet(): void
    {
        $result = $this->makeDeposit('non-existent-id', 100.00);

        $this->assertEquals(422, $result['response']->getStatusCode());
        $this->assertArrayHasKey('error', $result['data']);
        $this->assertEquals('Wallet not found', $result['data']['message']);
    }

    public function testDepositWithNegativeAmount(): void
    {
        $walletId = $this->createUserWithWallet();

        $result = $this->makeDeposit($walletId, -100.00);

        $this->assertEquals(400, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 0]);
        $this->assertArrayHasKey('error', $result['data']);
        $this->assertEquals('Money cannot be negative', $result['data']['message']);
    }

    public function testDepositWithZeroAmount(): void
    {
        $walletId = $this->createUserWithWallet();

        $result = $this->makeDeposit($walletId, 0);

        $this->assertEquals(400, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 0]);
        $this->assertArrayHasKey('error', $result['data']);
        $this->assertEquals('Transaction amount must be positive', $result['data']['message']);
    }

    public function testDepositWithNonNumericAmount(): void
    {
        $walletId = $this->createUserWithWallet();

        $response = $this->postJson("/api/v1/transactions/deposit/{$walletId}", [
            'amount' => 'not-a-number',
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 0]);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Amount must be numeric', $data['message']);
    }
}
