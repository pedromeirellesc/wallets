<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class WalletServiceTest extends TestCase
{
    private WalletService $walletService;
    private WalletRepositoryContract $walletRepository;
    private UserRepositoryContract $userRepository;
    private Wallet $wallet;

    public function setUp(): void
    {
        parent::setUp();

        $this->walletRepository = $this->createMock(WalletRepositoryContract::class);
        $this->userRepository = $this->createMock(UserRepositoryContract::class);
        $this->wallet = $this->createMock(Wallet::class);
        $this->walletService = new WalletService($this->walletRepository, $this->userRepository);
    }

    public function testCreateWalletForUserSuccessfully(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(User::create('John Doe', 'john@example.com', 'password', UserType::COMMON));

        $this->walletRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->willReturn(null);

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(Wallet::create(1));

        $output = $this->walletService->createWalletForUser(1);
        $this->assertInstanceOf(Wallet::class, $output);
        ;
    }

    public function testCreateWalletForUserWhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->walletService->createWalletForUser(1);
    }

    public function testCreateWalletForUserWhenUserAlreadyHasWallet(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(User::create('John Doe', 'john@example.com', 'password', UserType::COMMON));

        $this->walletRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->willReturn(Wallet::create(1));

        $this->expectException(\DomainException::class);

        $this->walletService->createWalletForUser(1);
    }

    public function testFindAllSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([
                Wallet::create(1),
                Wallet::create(2),
            ]);

        $output = $this->walletService->findAll();
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertInstanceOf(Wallet::class, $output[0]);
        $this->assertInstanceOf(Wallet::class, $output[1]);
    }

    public function testFindByIdSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(Wallet::create(1));

        $output = $this->walletService->findById(1);
        $this->assertInstanceOf(Wallet::class, $output);
    }

    public function testFindByIdWhenWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $output = $this->walletService->findById(1);
        $this->assertNull($output);
    }

    public function testDepositSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(Wallet::create(1));

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(Wallet::create(1)->deposit(Money::fromCents(100)));

        $output = $this->walletService->deposit(1, Money::fromCents(100));
        $this->assertInstanceOf(Wallet::class, $output);
        $this->assertEquals(Money::fromCents(100)->toCents(), $output->balance()->toCents());
    }

    public function testDepositWhenWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->walletService->deposit(1, Money::fromCents(100));
    }

    public function testWitdrawSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(Wallet::create(1)->deposit(Money::fromCents(100)));

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(Wallet::create(1)->deposit(Money::fromCents(100))->withdraw(Money::fromCents(100)));

        $output = $this->walletService->withdraw(1, Money::fromCents(100));
        $this->assertInstanceOf(Wallet::class, $output);
        $this->assertEquals(Money::fromCents(0)->toCents(), $output->balance()->toCents());
    }

    public function testWithdrawWhenWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->walletService->withdraw(1, Money::fromCents(100));
    }
}
