<?php

namespace Tests\Unit\Services;

use App\Exceptions\ValidationException;
use App\Models\Wallet;
use App\Validators\WalletValidator;
use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;
use App\Services\WalletService;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use Ramsey\Uuid\Uuid;

class WalletServiceTest extends TestCase
{

    private WalletService $walletService;
    private WalletRepositoryContract $walletRepository;
    private WalletValidator $walletValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->walletRepository = $this->createMock(WalletRepositoryContract::class);
        $this->walletValidator = $this->createMock(WalletValidator::class);
        $this->walletService = new WalletService($this->walletRepository, $this->walletValidator);
    }

    public function testCreateWalletSuccessfully(): void
    {
        $this->walletValidator
            ->expects($this->once())
            ->method('validateCreate')
            ->willReturn([]);

        $this->walletRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(new Wallet(1, new Money(100)));

        $this->walletService->createWalletForUser(1);
    }

    public function testCreateWalletWithInvalidFields(): void
    {
        $this->expectException(ValidationException::class);

        $this->walletValidator
            ->expects($this->once())
            ->method('validateCreate')
            ->willThrowException(new ValidationException(['user_id' => 'The user ID does not exist.']));

        $this->walletService->createWalletForUser(1);
    }

    public function testIndexSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([
                [
                    'id' => Uuid::uuid4()->toString(),
                    'user_id' => 1,
                    'balance' => 100
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'user_id' => 2,
                    'balance' => 200
                ],
            ]);

        $result = $this->walletService->index();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('user_id', $result[0]);
        $this->assertArrayHasKey('balance', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertArrayHasKey('user_id', $result[1]);
        $this->assertArrayHasKey('balance', $result[1]);
    }

    public function testShowSuccessfully(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(new Wallet(1, new Money(100)));

        $result = $this->walletService->show(1);

        $this->assertEquals(new Wallet(1, new Money(100)), $result);
    }

    public function testShowWalletNotFound(): void
    {
        $this->walletRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $result = $this->walletService->show(1);

        $this->assertNull($result);
    }
}