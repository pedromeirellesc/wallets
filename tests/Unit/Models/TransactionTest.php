<?php

namespace Tests\Unit\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testCreateDeposit(): void
    {
        $transaction = Transaction::createDeposit(
            'wallet-123',
            Money::fromCents(10000),
            'Test deposit',
        );

        $this->assertEquals(TransactionType::DEPOSIT, $transaction->type());
        $this->assertNull($transaction->fromWalletId());
        $this->assertEquals('wallet-123', $transaction->toWalletId());
        $this->assertEquals(10000, $transaction->amount()->toCents());
        $this->assertEquals('Test deposit', $transaction->description());
        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status());
        $this->assertTrue($transaction->isCompleted());
    }

    public function testCreateWithdrawal(): void
    {
        $transaction = Transaction::createWithdrawal(
            'wallet-123',
            Money::fromCents(5000),
            'Test withdrawal',
        );

        $this->assertEquals(TransactionType::WITHDRAW, $transaction->type());
        $this->assertEquals('wallet-123', $transaction->fromWalletId());
        $this->assertNull($transaction->toWalletId());
        $this->assertEquals(5000, $transaction->amount()->toCents());
        $this->assertEquals('Test withdrawal', $transaction->description());
        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status());
    }

    public function testCreateTransfer(): void
    {
        $transaction = Transaction::createTransfer(
            'wallet-from',
            'wallet-to',
            Money::fromCents(7500),
            'Test transfer',
        );

        $this->assertEquals(TransactionType::TRANSFER, $transaction->type());
        $this->assertEquals('wallet-from', $transaction->fromWalletId());
        $this->assertEquals('wallet-to', $transaction->toWalletId());
        $this->assertEquals(7500, $transaction->amount()->toCents());
        $this->assertEquals('Test transfer', $transaction->description());
        $this->assertEquals(TransactionStatus::PENDING, $transaction->status());
        $this->assertTrue($transaction->isPending());
    }

    public function testCannotTransferToSameWallet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same wallet');

        Transaction::createTransfer(
            'wallet-123',
            'wallet-123',
            Money::fromCents(1000),
        );
    }

    public function testCannotCreateTransactionWithZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Transaction::createDeposit(
            'wallet-123',
            Money::fromCents(0),
        );
    }

    public function testCompleteTransaction(): void
    {
        $transaction = Transaction::createTransfer(
            'wallet-from',
            'wallet-to',
            Money::fromCents(1000),
        );

        $this->assertTrue($transaction->isPending());

        $completed = $transaction->complete();

        $this->assertTrue($completed->isCompleted());
        $this->assertEquals(TransactionStatus::COMPLETED, $completed->status());
    }

    public function testCannotCompleteAlreadyCompletedTransaction(): void
    {
        $transaction = Transaction::createDeposit('wallet-123', Money::fromCents(1000));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transaction is already completed');

        $transaction->complete();
    }

    public function testFailTransaction(): void
    {
        $transaction = Transaction::createTransfer(
            'wallet-from',
            'wallet-to',
            Money::fromCents(1000),
        );

        $failed = $transaction->fail();

        $this->assertTrue($failed->isFailed());
        $this->assertEquals(TransactionStatus::FAILED, $failed->status());
    }

    public function testCannotFailCompletedTransaction(): void
    {
        $transaction = Transaction::createDeposit('wallet-123', Money::fromCents(1000));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot fail a completed transaction');

        $transaction->fail();
    }

    public function testTransactionIsImmutable(): void
    {
        $original = Transaction::createTransfer(
            'wallet-from',
            'wallet-to',
            Money::fromCents(1000),
        );

        $completed = $original->complete();

        $this->assertTrue($original->isPending());
        $this->assertTrue($completed->isCompleted());
        $this->assertNotSame($original, $completed);
    }
}
