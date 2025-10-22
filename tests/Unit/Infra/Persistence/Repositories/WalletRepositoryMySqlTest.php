<?php

namespace Tests\Unit\Infra\Persistence\Repositories;

use App\Infra\Persistence\Repositories\WalletRepositoryMySql;
use App\Models\Wallet;
use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;
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

    public function testSaveWalletSuccessfully(): void
    {
        $wallet = new Wallet(1, new Money(100));
        $wallet = $this->repository->save($wallet);

        $this->assertEquals($wallet->userId(), $wallet->userId());
        $this->assertEquals($wallet->balance()->toCents(), $wallet->balance()->toCents());
    }

    public function testFindWalletByIdSuccessfully(): void
    {
        $expectedWallet = new Wallet(1, new Money(100));
        $expectedWallet->setId(Uuid::uuid4()->toString());

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM wallets WHERE id = :id')
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
            ]);

        $result = $this->repository->findById($expectedWallet->id());
        $this->assertEquals($expectedWallet, $result);
        $this->assertIsObject($result);
        $this->assertEquals($expectedWallet->id(), $result->id());
        $this->assertEquals($expectedWallet->userId(), $result->userId());
        $this->assertEquals($expectedWallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testFindAllWalletsSuccessfully(): void
    {
        $expectedWallets = [
            [
                'id' => Uuid::uuid4()->toString(),
                'user_id' => 1,
                'balance' => 100,
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'user_id' => 2,
                'balance' => 200,
            ],
        ];

        $this->pdoMock
            ->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM wallets')
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => $expectedWallets[0]['id'],
                    'user_id' => $expectedWallets[0]['user_id'],
                    'balance' => $expectedWallets[0]['balance'],
                ],
                [
                    'id' => $expectedWallets[1]['id'],
                    'user_id' => $expectedWallets[1]['user_id'],
                    'balance' => $expectedWallets[1]['balance'],
                ],
            ]);

        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertEquals($expectedWallets[0], $result[0]);
        $this->assertEquals($expectedWallets[1], $result[1]);
    }

    public function testHydrateWalletSuccessfully(): void
    {
        $walletData = [
            'id' => Uuid::uuid4()->toString(),
            'user_id' => 1,
            'balance' => 100,
        ];

        $wallet = $this->repository->hydrate($walletData);
        $this->assertIsObject($wallet);
        $this->assertEquals($walletData['id'], $wallet->id());
        $this->assertEquals($walletData['user_id'], $wallet->userId());
        $this->assertEquals($walletData['balance'], $wallet->balance()->toCents());
    }
}