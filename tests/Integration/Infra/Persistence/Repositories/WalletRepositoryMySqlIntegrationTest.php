<?php

namespace Tests\Integration\Infra\Persistence\Repositories;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\UserRepositoryMySql;
use App\Infra\Persistence\Repositories\WalletRepositoryMySql;
use App\Models\User;
use App\Models\Wallet;
use Ramsey\Uuid\Uuid;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class WalletRepositoryMySqlIntegrationTest extends AppTestCase
{
    use DatabaseAssertions;

    private WalletRepositoryMySql $repository;
    private UserRepositoryMySql $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new WalletRepositoryMySql(self::getConnection());
        $this->userRepository = new UserRepositoryMySql(self::getConnection());
    }

    public function testSaveInsertWalletSuccessfully(): void
    {
        $user = User::create('Test User', 'test@example.com', 'password', UserType::COMMON);
        $user = $this->userRepository->save($user);
        $wallet = Wallet::create($user->id());

        $this->repository->save($wallet);

        $walletData = [
            'id' => $wallet->id(),
            'user_id' => $wallet->userId(),
            'balance' => $wallet->balance()->toCents(),
        ];
        $this->assertDatabaseHas('wallets', $walletData);
    }

    public function testFindByIdReturnsWallet(): void
    {
        $user = User::create('Test User', 'test@example.com', 'password', UserType::COMMON);
        $user = $this->userRepository->save($user);
        $wallet = Wallet::create($user->id());
        $wallet = $this->repository->save($wallet);

        $result = $this->repository->findById($wallet->id());

        $this->assertIsObject($result);
        $this->assertInstanceOf(Wallet::class, $result);
        $this->assertEquals($wallet->id(), $result->id());
        $this->assertEquals($wallet->userId(), $result->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result->balance()->toCents());
    }

    public function testFindByIdReturnsNullWhenWalletNotFound(): void
    {
        $result = $this->repository->findById(Uuid::uuid4()->toString());
        $this->assertNull($result);
    }

    public function testFindAllReturnsAllWallets(): void
    {
        $user = User::create('Test User', 'test@example.com', 'password', UserType::COMMON);
        $user = $this->userRepository->save($user);
        $wallet = Wallet::create($user->id());
        $wallet = $this->repository->save($wallet);

        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($wallet->id(), $result[0]->id());
        $this->assertEquals($wallet->userId(), $result[0]->userId());
        $this->assertEquals($wallet->balance()->toCents(), $result[0]->balance()->toCents());
    }

    public function testFindAllReturnsEmptyArrayWhenNoWalletsFound(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
