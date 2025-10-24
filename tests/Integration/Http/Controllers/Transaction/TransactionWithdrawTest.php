<?php

namespace Http\Controllers\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserType;
use Tests\AppTestCase;
use Tests\Traits\CreatesUsers;
use Tests\Traits\DatabaseAssertions;
use Tests\Traits\MakesTransactions;

class TransactionWithdrawTest extends AppTestCase
{
    use DatabaseAssertions;
    use CreatesUsers;
    use MakesTransactions;

    public function testWithdrawSuccessfully(): void
    {
        $walletId = $this->createUserWithWallet('John Doe', 'john@example.com', UserType::COMMON);

        $this->makeDeposit($walletId, 100.00);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 10000,
        ]);

        $response = $this->makeWithdrawal($walletId, 100.00);

        $this->assertEquals(201, $response['response']->getStatusCode());
        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $walletId,
            'amount' => 10000,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0,
        ]);
    }

    public function testWithdrawInsufficientFunds(): void
    {
        $walletId = $this->createUserWithWallet('John Doe', 'john@example.com', UserType::COMMON);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0,
        ]);

        $response = $this->makeWithdrawal($walletId, 1.00);

        $this->assertEquals(422, $response['response']->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'from_wallet_id' => $walletId,
            'amount' => 100,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 0,
        ]);
    }

    public function testWithdrawInexistentWallet(): void
    {
        $response = $this->makeWithdrawal('non-existent-id', 1.00);

        $this->assertEquals(422, $response['response']->getStatusCode());
        $this->assertDatabaseMissing('transactions', [
            'from_wallet_id' => 'non-existent-id',
            'amount' => 100,
            'type' => TransactionType::WITHDRAW->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
    }

    public function testWithdrawWithNegativeAmount(): void
    {
        $walletId = $this->createUserWithWallet('John Doe', 'john@example.com');
        $this->makeDeposit($walletId, 100.00);

        $response = $this->makeWithdrawal($walletId, -10.00);

        $this->assertEquals(400, $response['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 10000,
        ]);
    }

    public function testWithdrawWithZeroAmount(): void
    {
        $walletId = $this->createUserWithWallet('John Doe', 'john@example.com');
        $this->makeDeposit($walletId, 100.00);

        $response = $this->makeWithdrawal($walletId, 0.00);

        $this->assertEquals(400, $response['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => 10000,
        ]);
    }
}
