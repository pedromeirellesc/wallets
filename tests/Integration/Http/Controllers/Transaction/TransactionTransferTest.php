<?php

namespace Http\Controllers\Transaction;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\UserType;
use Tests\AppTestCase;
use Tests\Traits\CreatesUsers;
use Tests\Traits\DatabaseAssertions;
use Tests\Traits\MakesTransactions;

class TransactionTransferTest extends AppTestCase
{
    use DatabaseAssertions;
    use CreatesUsers;
    use MakesTransactions;

    public function testTransferSuccessfully(): void
    {
        $senderWalletId = $this->createUserWithWallet('Sender', 'sender@example.com', UserType::COMMON);
        $receiverWalletId = $this->createUserWithWallet('Receiver', 'receiver@example.com', UserType::COMMON);

        $depositResult = $this->makeDeposit($senderWalletId, 100.00);

        $this->assertEquals(
            201,
            $depositResult['response']->getStatusCode(),
            'Deposit failed: ' . json_encode($depositResult['data']),
        );

        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 10000]);
        $this->assertDatabaseHas('wallets', ['id' => $receiverWalletId, 'balance' => 0]);

        $result = $this->makeTransfer($senderWalletId, $receiverWalletId, 50.00);

        $this->assertEquals(
            201,
            $result['response']->getStatusCode(),
            'Transfer failed: ' . json_encode($result['data']),
        );

        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $senderWalletId,
            'to_wallet_id' => $receiverWalletId,
            'amount' => 5000,
            'type' => TransactionType::TRANSFER->value,
            'status' => TransactionStatus::COMPLETED->value,
        ]);
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 5000]);
        $this->assertDatabaseHas('wallets', ['id' => $receiverWalletId, 'balance' => 5000]);
    }

    public function testTransferWithInsufficientFunds(): void
    {
        $senderWalletId = $this->createUserWithWallet('Sender', 'sender@example.com');
        $receiverWalletId = $this->createUserWithWallet('Receiver', 'receiver@example.com');

        $result = $this->makeTransfer($senderWalletId, $receiverWalletId, 100.00);

        if ($result['response']->getStatusCode() !== 422) {
            $body = json_encode($result['data'], JSON_PRETTY_PRINT);
            $this->fail("Expected 422, got {$result['response']->getStatusCode()}. Response: {$body}");
        }

        $this->assertEquals(422, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 0]);
        $this->assertDatabaseHas('wallets', ['id' => $receiverWalletId, 'balance' => 0]);
    }

    public function testTransferToSameWallet(): void
    {
        $walletId = $this->createUserWithWallet();
        $this->makeDeposit($walletId, 100.00);

        $result = $this->makeTransfer($walletId, $walletId, 50.00);

        $this->assertEquals(400, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $walletId, 'balance' => 10000]);
    }

    public function testTransferWithNonExistentSourceWallet(): void
    {
        $receiverWalletId = $this->createUserWithWallet();

        $result = $this->makeTransfer('non-existent', $receiverWalletId, 50.00);

        $this->assertEquals(422, $result['response']->getStatusCode());
    }

    public function testTransferWithNonExistentDestinationWallet(): void
    {
        $senderWalletId = $this->createUserWithWallet();
        $this->makeDeposit($senderWalletId, 100.00);

        $result = $this->makeTransfer($senderWalletId, 'non-existent', 50.00);

        $this->assertEquals(422, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 10000]);
    }

    public function testTransferWithNegativeAmount(): void
    {
        $senderWalletId = $this->createUserWithWallet('Sender', 'sender@example.com');
        $receiverWalletId = $this->createUserWithWallet('Receiver', 'receiver@example.com');
        $this->makeDeposit($senderWalletId, 100.00);

        $result = $this->makeTransfer($senderWalletId, $receiverWalletId, -50.00);

        $this->assertEquals(400, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 10000]);
    }

    public function testTransferWithZeroAmount(): void
    {
        $senderWalletId = $this->createUserWithWallet('Sender', 'sender@example.com');
        $receiverWalletId = $this->createUserWithWallet('Receiver', 'receiver@example.com');
        $this->makeDeposit($senderWalletId, 100.00);

        $result = $this->makeTransfer($senderWalletId, $receiverWalletId, 0.00);

        $this->assertEquals(400, $result['response']->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 10000]);
    }

    public function testTransferWithNonNumericAmount(): void
    {
        $senderWalletId = $this->createUserWithWallet('Sender', 'sender@example.com');
        $receiverWalletId = $this->createUserWithWallet('Receiver', 'receiver@example.com');
        $this->makeDeposit($senderWalletId, 100.00);

        $response = $this->postJson('/api/v1/transactions/transfer', [
            'from_wallet_id' => $senderWalletId,
            'to_wallet_id' => $receiverWalletId,
            'amount' => 'invalid-amount',
        ]);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertDatabaseHas('wallets', ['id' => $senderWalletId, 'balance' => 10000]);
    }
}
