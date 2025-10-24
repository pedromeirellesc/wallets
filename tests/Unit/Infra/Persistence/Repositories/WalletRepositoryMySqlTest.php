<?php

namespace Tests\Unit\Infra\Persistence\Repositories;

use App\Infra\Persistence\Repositories\WalletRepositoryMySql;
use App\Models\Wallet;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class WalletRepositoryMySqlTest extends TestCase
{
    private WalletRepositoryMySql $repository;
    private PDO $pdoMock;
    private PDOStatement $stmtMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);

        $this->pdoMock
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->repository = new WalletRepositoryMySql($this->pdoMock);
    }

    public function testSaveWalletInexistentSuccessfully(): void
    {
        $wallet = Wallet::create(1);

        $repository = $this->getMockBuilder(WalletRepositoryMySql::class)
            ->setConstructorArgs([$this->pdoMock])
            ->onlyMethods(['insert', 'update', 'findById'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findById')
            ->with($wallet->id())
            ->willReturn(null);

        $repository->expects($this->once())
            ->method('insert')
            ->with($this->identicalTo($wallet))
            ->willReturn($wallet);

        $repository->expects($this->never())
            ->method('update');

        $result = $repository->save($wallet);

        $this->assertEquals($wallet->userId(), $result->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testSaveWalletExistentSuccessfully(): void
    {
        $wallet = Wallet::create(1);

        $repository = $this->getMockBuilder(WalletRepositoryMySql::class)
            ->setConstructorArgs([$this->pdoMock])
            ->onlyMethods(['insert', 'update', 'findById'])
            ->getMock();

        $repository->expects($this->once())
            ->method('findById')
            ->with($wallet->id())
            ->willReturn($wallet);

        $repository->expects($this->never())
            ->method('insert');

        $repository->expects($this->once())
            ->method('update')
            ->with($this->identicalTo($wallet))
            ->willReturn($wallet);

        $result = $repository->save($wallet);

        $this->assertEquals($wallet->userId(), $result->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testInsertWallet(): void
    {
        $wallet = Wallet::create(1);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with(
                'INSERT INTO wallets (id, user_id, balance, created_at, updated_at) VALUES (:id, :user_id, :balance, :created_at, :updated_at)',
            )
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':id' => $wallet->id(),
                ':user_id' => $wallet->userId(),
                ':balance' => $wallet->balance()->toCents(),
                ':created_at' => $wallet->createdAt()->format('Y-m-d H:i:s'),
                ':updated_at' => $wallet->updatedAt()->format('Y-m-d H:i:s'),
            ]);

        $result = $this->repository->insert($wallet);
        $this->assertIsObject($result);
        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($wallet->id(), $result->id());
        $this->assertEquals($wallet->userId(), $result->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result->balance()->toCents());
        $this->assertEquals($wallet->createdAt(), $result->createdAt());
        $this->assertEquals($wallet->updatedAt(), $result->updatedAt());
    }

    public function testUpdateWallet(): void
    {
        $wallet = Wallet::create(1);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with('UPDATE wallets SET balance = :balance, updated_at = :updated_at WHERE id = :id')
            ->willReturn($this->stmtMock);

        $this->stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':id' => $wallet->id(),
                ':balance' => $wallet->balance()->toCents(),
                ':updated_at' => $wallet->updatedAt()->format('Y-m-d H:i:s'),
            ]);

        $result = $this->repository->update($wallet);
        $this->assertIsObject($result);
        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($wallet->id(), $result->id());
        $this->assertEquals($wallet->userId(), $result->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result->balance()->toCents());
        $this->assertEquals($wallet->createdAt(), $result->createdAt());
        $this->assertEquals($wallet->updatedAt(), $result->updatedAt());
    }

    public function testFindWalletByIdSuccessfully(): void
    {
        $expectedWallet = Wallet::create(1);

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE id = :id')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':id' => $expectedWallet->id()]);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => $expectedWallet->id(),
                'user_id' => $expectedWallet->userId(),
                'balance' => $expectedWallet->balance()->toCents(),
                'created_at' => $expectedWallet->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $expectedWallet->updatedAt()->format('Y-m-d H:i:s'),
            ]);

        $result = $this->repository->findById($expectedWallet->id());
        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($expectedWallet->id(), $result->id());
        $this->assertEquals($expectedWallet->userId(), $result->userId());
        $this->assertEquals($expectedWallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testFindWalletByIdWhenWalletDoesNotExist(): void
    {
        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE id = :id')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':id' => '1']);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $result = $this->repository->findById('1');
        $this->assertNull($result);
    }

    public function testFindWalletByUserIdSuccessfully(): void
    {
        $expectedWallet = Wallet::create(1);

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE user_id = :user_id')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':user_id' => $expectedWallet->userId()]);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => $expectedWallet->id(),
                'user_id' => $expectedWallet->userId(),
                'balance' => $expectedWallet->balance()->toCents(),
                'created_at' => $expectedWallet->createdAt()->format('Y-m-d H:i:s'),
                'updated_at' => $expectedWallet->updatedAt()->format('Y-m-d H:i:s'),
            ]);

        $result = $this->repository->findByUserId($expectedWallet->userId());
        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($expectedWallet->id(), $result->id());
        $this->assertEquals($expectedWallet->userId(), $result->userId());
        $this->assertEquals($expectedWallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testFindWalletByUserIdWhenWalletDoesNotExist(): void
    {
        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE user_id = :user_id')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':user_id' => 1]);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $result = $this->repository->findByUserId(1);
        $this->assertNull($result);
    }

    public function testFindAllWalletsSuccessfully(): void
    {
        $expectedWallets = [
            Wallet::create(1),
            Wallet::create(2),
        ];

        $this->pdoMock
            ->expects($this->once())
            ->method('query')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => $expectedWallets[0]->id(),
                    'user_id' => $expectedWallets[0]->userId(),
                    'balance' => $expectedWallets[0]->balance()->toCents(),
                    'created_at' => $expectedWallets[0]->createdAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $expectedWallets[0]->updatedAt()->format('Y-m-d H:i:s'),
                ],
                [
                    'id' => $expectedWallets[1]->id(),
                    'user_id' => $expectedWallets[1]->userId(),
                    'balance' => $expectedWallets[1]->balance()->toCents(),
                    'created_at' => $expectedWallets[1]->createdAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $expectedWallets[1]->updatedAt()->format('Y-m-d H:i:s'),
                ],
            ]);

        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertInstanceOf(Wallet::class, $result[0]);
        $this->assertInstanceOf(Wallet::class, $result[1]);
        $this->assertSameSize($expectedWallets, $result);
    }

    public function testFindAllWalletsWhenNoWalletsExist(): void
    {
        $this->pdoMock
            ->expects($this->once())
            ->method('query')
            ->with('SELECT id, user_id, balance, created_at, updated_at FROM wallets')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testHydrateWalletSuccessfully(): void
    {
        $walletData = [
            'id' => Uuid::uuid4()->toString(),
            'user_id' => 1,
            'balance' => 100,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ];

        $wallet = $this->repository->hydrate($walletData);
        $this->assertIsObject($wallet);
        $this->assertEquals($walletData['id'], $wallet->id());
        $this->assertEquals($walletData['user_id'], $wallet->userId());
        $this->assertEquals($walletData['balance'], $wallet->balance()->toCents());
        $this->assertEquals($walletData['created_at'], $wallet->createdAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($walletData['updated_at'], $wallet->updatedAt()->format('Y-m-d H:i:s'));
    }
}
