<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testConstructorSetsAmount(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals(100, $money->toCents());
    }

    public function testMoneyFromCents(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals(100, $money->toCents());
        $this->assertInstanceOf(Money::class, $money);
    }

    public function testMoneyFromCentsWhenNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromCents(-100);
    }

    public function testMoneyFromFloat(): void
    {
        $money = Money::fromFloat(10.5);

        $this->assertEquals(1050, $money->toCents());
    }

    public function testMoneyZero(): void
    {
        $money = Money::zero();

        $this->assertEquals(0, $money->toCents());
    }

    public function testMoneyAdd(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $result = $money->add($otherMoney);

        $this->assertEquals(300, $result->toCents());
    }

    public function testMoneySubtract(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->expectException(\InvalidArgumentException::class);
        ;

        $money->subtract($otherMoney);
    }

    public function testMoneySubtractWhenResultIsNegative(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->expectException(\InvalidArgumentException::class);
        ;

        $money->subtract($otherMoney);
    }

    public function testIsPositive(): void
    {
        $money = Money::fromCents(100);

        $this->assertTrue($money->isPositive());
    }

    public function testIsNotPositive(): void
    {
        $money = Money::fromCents(0);

        $this->assertFalse($money->isPositive());
    }

    public function testIsZero(): void
    {
        $money = Money::zero();

        $this->assertTrue($money->isZero());
    }

    public function testIsLessThan(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->assertTrue($money->isLessThan($otherMoney));
    }

    public function testIsGreaterThan(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->assertTrue($otherMoney->isGreaterThan($money));
    }

    public function testMoneyToCents(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals(100, $money->toCents());
    }

    public function testMoneyToFloat(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals(1.0, $money->toFloat());
    }

    public function testMoneyFormat(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals('R$ 1,00', $money->format());
    }

    public function testMoneyEquals(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(100);

        $this->assertTrue($money->equals($otherMoney));
    }
}
