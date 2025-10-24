<?php

namespace Tests\Unit\Services;

use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Wallet;
use App\Services\TransactionService;
use App\ValueObjects\Money;
use PDO;
use PHPUnit\Framework\TestCase;

class TransactionServiceTest extends TestCase
{
    private TransactionRepositoryContract $transactionRepository;
    private WalletRepositoryContract $walletRepository;
    private PDO $pdo;
    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionRepository = $this->createMock(TransactionRepositoryContract::class);
        $this->walletRepository = $this->createMock(WalletRepositoryContract::class);
        $this->pdo = $this->createMock(PDO::class);

        $this->service = new TransactionService(
            $this->transactionRepository,
            $this->walletRepository,
            $this->pdo,
        );
    }

    public function testDepositSuccessfully(): void
    {
        $walletId = 'wallet-123';
        $amount = Money::fromCents(10000);
        $wallet = Wallet::create(1)->withId($walletId);

        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->with($walletId)
            ->willReturn($wallet);

        $this->pdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($w) use ($amount) {
                return $w->balance()->equals($amount);
            }));

        $this->transactionRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($transaction) {
                return $transaction->withId('trans-123');
            });

        $this->pdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->service->deposit($walletId, $amount);

        $this->assertEquals('trans-123', $result->id());
        $this->assertEquals(10000, $result->amount()->toCents());
    }

    public function testDepositWithExistingTransaction(): void
    {
        $walletId = 'wallet-123';
        $amount = Money::fromCents(10000);
        $wallet = Wallet::create(1)->withId($walletId);

        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->with($walletId)
            ->willReturn($wallet);

        $this->pdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->pdo
            ->expects($this->never())
            ->method('beginTransaction');

        $this->walletRepository
            ->expects($this->once())
            ->method('save');

        $this->transactionRepository
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($transaction) {
                return $transaction->withId('trans-123');
            });

        $this->pdo
            ->expects($this->never())
            ->method('commit');

        $result = $this->service->deposit($walletId, $amount);

        $this->assertEquals('trans-123', $result->id());
    }

    public function testDepositThrowsExceptionWhenWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Wallet not found');

        $this->service->deposit('non-existent', Money::fromCents(1000));
    }

    public function testDepositRollsBackOnError(): void
    {
        $walletId = 'wallet-123';
        $wallet = Wallet::create(1)->withId($walletId);

        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($wallet);

        $this->pdo
            ->expects($this->exactly(2))
            ->method('inTransaction')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->pdo
            ->expects($this->once())
            ->method('rollBack');

        $this->expectException(\Exception::class);

        $this->service->deposit($walletId, Money::fromCents(1000));
    }

    public function testDepositDoesNotRollbackExistingTransaction(): void
    {
        $walletId = 'wallet-123';
        $wallet = Wallet::create(1)->withId($walletId);

        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($wallet);

        $this->pdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(true);

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->pdo
            ->expects($this->never())
            ->method('rollBack');

        $this->expectException(\Exception::class);

        $this->service->deposit($walletId, Money::fromCents(1000));
    }

    public function testTransferSuccessfully(): void
    {
        $fromWalletId = 'wallet-from';
        $toWalletId = 'wallet-to';
        $amount = Money::fromCents(5000);

        $fromWallet = Wallet::create(1)
            ->withId($fromWalletId)
            ->deposit(Money::fromCents(10000));

        $toWallet = Wallet::create(2)->withId($toWalletId);

        $this->walletRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function ($id) use ($fromWallet, $toWallet, $fromWalletId) {
                return $id === $fromWalletId ? $fromWallet : $toWallet;
            });

        $this->pdo
            ->expects($this->once())
            ->method('inTransaction')
            ->willReturn(false);

        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->transactionRepository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($transaction) {
                if (empty($transaction->id())) {
                    return $transaction->withId('trans-123');
                }
                return $transaction;
            });

        $this->walletRepository
            ->expects($this->exactly(2))
            ->method('save');

        $this->pdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->service->transfer($fromWalletId, $toWalletId, $amount);

        $this->assertTrue($result->isCompleted());
        $this->assertEquals(5000, $result->amount()->toCents());
    }

    public function testTransferFailsWithInsufficientFunds(): void
    {
        $fromWalletId = 'wallet-from';
        $toWalletId = 'wallet-to';
        $amount = Money::fromCents(10000);

        $fromWallet = Wallet::create(1)->withId($fromWalletId);
        $toWallet = Wallet::create(2)->withId($toWalletId);

        $this->walletRepository
            ->expects($this->exactly(2))
            ->method('findById')
            ->willReturnCallback(function ($id) use ($fromWallet, $toWallet, $fromWalletId) {
                return $id === $fromWalletId ? $fromWallet : $toWallet;
            });

        $this->pdo
            ->expects($this->exactly(2))
            ->method('inTransaction')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->transactionRepository
            ->expects($this->atLeastOnce())
            ->method('save')
            ->willReturnCallback(function ($transaction) {
                if (empty($transaction->id())) {
                    return $transaction->withId('trans-123');
                }
                return $transaction;
            });

        $this->pdo
            ->expects($this->once())
            ->method('rollBack');

        $this->expectException(\DomainException::class);

        $this->service->transfer($fromWalletId, $toWalletId, $amount);
    }
}
