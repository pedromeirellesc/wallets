<?php

namespace Tests\Unit\Infra\Persistence\Repositories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Infra\Persistence\Repositories\TransactionRepositoryMySql;
use App\Models\Transaction;
use App\ValueObjects\Money;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TransactionRepositoryMySqlTest extends TestCase
{
    private PDO $pdo;
    private TransactionRepositoryMySql $repository;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->repository = new TransactionRepositoryMySql($this->pdo);
    }

    public function testSaveNewTransactionCallsInsert(): void
    {
        $transaction = Transaction::createDeposit('wallet-123', Money::fromFloat(100.00));

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $stmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->repository->save($transaction);

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals($transaction->id(), $result->id());
    }

    public function testSaveExistingTransactionCallsUpdate(): void
    {
        $transaction = Transaction::reconstitute(
            'existing-id',
            TransactionType::TRANSFER,
            'wallet-1',
            'wallet-2',
            Money::fromFloat(100.00),
            'Transfer',
            TransactionStatus::PENDING,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $existingData = [
            'id' => 'existing-id',
            'type' => TransactionType::TRANSFER->value,
            'from_wallet_id' => 'wallet-1',
            'to_wallet_id' => 'wallet-2',
            'amount' => 10000,
            'description' => 'Transfer',
            'status' => TransactionStatus::PENDING->value,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ];

        $findStmt = $this->createMock(PDOStatement::class);
        $findStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $findStmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($existingData);

        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($findStmt, $updateStmt);

        $result = $this->repository->save($transaction);

        $this->assertInstanceOf(Transaction::class, $result);
    }

    public function testInsertTransaction(): void
    {
        $transaction = Transaction::createDeposit('wallet-123', Money::fromFloat(100.00), 'Test deposit');

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($transaction) {
                return $params[':id'] === $transaction->id()
                    && $params[':type'] === TransactionType::DEPOSIT->value
                    && $params[':from_wallet_id'] === null
                    && $params[':to_wallet_id'] === 'wallet-123'
                    && $params[':amount'] === 10000
                    && $params[':description'] === 'Test deposit'
                    && $params[':status'] === TransactionStatus::COMPLETED->value;
            }))
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO transactions'))
            ->willReturn($stmt);

        $result = $this->repository->insert($transaction);

        $this->assertEquals($transaction->id(), $result->id());
    }

    public function testUpdateTransaction(): void
    {
        $transaction = Transaction::reconstitute(
            'transaction-123',
            TransactionType::TRANSFER,
            'wallet-1',
            'wallet-2',
            Money::fromFloat(100.00),
            'Transfer',
            TransactionStatus::COMPLETED,
            new DateTimeImmutable('2025-01-01 10:00:00'),
            new DateTimeImmutable(),
        );

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($transaction) {
                return $params[':id'] === 'transaction-123'
                    && $params[':status'] === TransactionStatus::COMPLETED->value
                    && isset($params[':updated_at']);
            }))
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE transactions'))
            ->willReturn($stmt);

        $result = $this->repository->update($transaction);

        $this->assertEquals($transaction, $result);
    }

    public function testFindByIdReturnsTransaction(): void
    {
        $transactionData = [
            'id' => 'transaction-123',
            'type' => TransactionType::DEPOSIT->value,
            'from_wallet_id' => null,
            'to_wallet_id' => 'wallet-123',
            'amount' => 10000,
            'description' => 'Test deposit',
            'status' => TransactionStatus::COMPLETED->value,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:05:00',
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 'transaction-123'])
            ->willReturn(true);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($transactionData);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM transactions WHERE id = :id'))
            ->willReturn($stmt);

        $result = $this->repository->findById('transaction-123');

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals('transaction-123', $result->id());
        $this->assertEquals(TransactionType::DEPOSIT, $result->type());
        $this->assertNull($result->fromWalletId());
        $this->assertEquals('wallet-123', $result->toWalletId());
        $this->assertEquals(10000, $result->amount()->toCents());
        $this->assertEquals('Test deposit', $result->description());
        $this->assertEquals(TransactionStatus::COMPLETED, $result->status());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->repository->findById('non-existent');

        $this->assertNull($result);
    }

    public function testHydrateCreatesTransactionFromArray(): void
    {
        $data = [
            'id' => 'transaction-123',
            'type' => TransactionType::TRANSFER->value,
            'from_wallet_id' => 'wallet-1',
            'to_wallet_id' => 'wallet-2',
            'amount' => 5000,
            'description' => 'Transfer',
            'status' => TransactionStatus::COMPLETED->value,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:05:00',
        ];

        $transaction = $this->repository->hydrate($data);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('transaction-123', $transaction->id());
        $this->assertEquals(TransactionType::TRANSFER, $transaction->type());
        $this->assertEquals('wallet-1', $transaction->fromWalletId());
        $this->assertEquals('wallet-2', $transaction->toWalletId());
        $this->assertEquals(5000, $transaction->amount()->toCents());
        $this->assertEquals('Transfer', $transaction->description());
        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status());
    }

    public function testHydrateWithNullFromWalletId(): void
    {
        $data = [
            'id' => 'transaction-123',
            'type' => TransactionType::DEPOSIT->value,
            'from_wallet_id' => null,
            'to_wallet_id' => 'wallet-123',
            'amount' => 10000,
            'description' => 'Deposit',
            'status' => TransactionStatus::COMPLETED->value,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ];

        $transaction = $this->repository->hydrate($data);

        $this->assertNull($transaction->fromWalletId());
        $this->assertEquals('wallet-123', $transaction->toWalletId());
    }

    public function testHydrateWithNullToWalletId(): void
    {
        $data = [
            'id' => 'transaction-123',
            'type' => TransactionType::WITHDRAW->value,
            'from_wallet_id' => 'wallet-123',
            'to_wallet_id' => null,
            'amount' => 5000,
            'description' => 'Withdrawal',
            'status' => TransactionStatus::COMPLETED->value,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ];

        $transaction = $this->repository->hydrate($data);

        $this->assertEquals('wallet-123', $transaction->fromWalletId());
        $this->assertNull($transaction->toWalletId());
    }
}
