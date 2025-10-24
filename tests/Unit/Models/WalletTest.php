<?php

namespace Tests\Unit\Models;

use App\Models\Wallet;
use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class WalletTest extends TestCase
{
    private Money $money;

    public function setUp(): void
    {
        parent::setUp();

        $this->money = $this->createMock(Money::class);
    }

    public function testCreateSuccessfully(): void
    {
        $wallet = Wallet::create(1);

        $this->assertInstanceOf(Wallet::class, $wallet);
    }

    public function testReconstituteSuccessfully(): void
    {
        $wallet = Wallet::reconstitute(1, 1, Money::fromCents(1000), new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertInstanceOf(Wallet::class, $wallet);
    }

    public function testWithIdSuccessfully(): void
    {
        $wallet = Wallet::create(1);
        $wallet = $wallet->withId(1);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(1, $wallet->id());
    }

    public function testDepositSuccessfully(): void
    {
        $wallet = Wallet::create(1);
        $wallet = $wallet->deposit(Money::fromCents(1000));

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(1000, $wallet->balance()->toCents());
    }

    public function testWithdrawSuccessfully(): void
    {
        $wallet = Wallet::create(1);
        $wallet = $wallet->deposit(Money::fromCents(1000));
        $wallet = $wallet->withdraw(Money::fromCents(1000));

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->balance()->toCents());
    }

    public function testWithdrawWhenAmountGreaterThanBalanceThrowException(): void
    {
        $this->money->method('isGreaterThanOrEqual')->willReturn(false);

        $this->expectException(\DomainException::class);
        $wallet = Wallet::create(1);
        $wallet->deposit(Money::fromCents(1000));
        $wallet->withdraw(Money::fromCents(2000));
    }

    public function testCanWithdrawSuccessfully(): void
    {
        $wallet = Wallet::create(1);
        $wallet = $wallet->deposit(Money::fromCents(1000));
        $canWithdraw = $wallet->canWithdraw(Money::fromCents(1000));

        $this->assertTrue($canWithdraw);
    }
}
