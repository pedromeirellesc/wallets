<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function testConstructorSetsAmount(): void
    {
        $money = new Money(100);

        $this->assertEquals(100, $money->toCents());
    }

    public function testMoneyFromCents(): void
    {
        $money = Money::fromCents(100);

        $this->assertEquals(100, $money->toCents());
    }

    public function testMoneyFromFloat(): void
    {
        $money = Money::fromFloat(10.5);

        $this->assertEquals(1050, $money->toCents());
    }

    public function testMoneyIsZero(): void
    {
        $money = Money::zero();

        $this->assertTrue($money->isZero());
    }

    public function testAddMoney(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $result = $money->add($otherMoney);

        $this->assertEquals(300, $result->toCents());
    }

    public function testSubtractMoney(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $result = $money->subtract($otherMoney);

        $this->assertEquals(-100, $result->toCents());
    }

    public function testIsGreaterThan(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->assertTrue($otherMoney->isGreaterThan($money));
    }

    public function testIsLessThan(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->assertTrue($money->isLessThan($otherMoney));
    }

    public function testIsEqual(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(100);

        $this->assertTrue($money->equals($otherMoney));
    }

    public function testIsNotEqual(): void
    {
        $money = Money::fromCents(100);
        $otherMoney = Money::fromCents(200);

        $this->assertFalse($money->equals($otherMoney));
    }

    public function testIsPositive(): void
    {
        $money = Money::fromCents(100);

        $this->assertTrue($money->isPositive());
        $this->assertFalse($money->isNegative());
    }

    public function testIsNegative(): void
    {
        $money = Money::fromCents(-100);

        $this->assertFalse($money->isPositive());
        $this->assertTrue($money->isNegative());
    }

    public function testIsZero(): void
    {
        $money = Money::zero();

        $this->assertTrue($money->isZero());
    }

    public function testFormatToFloat(): void
    {
        $money = Money::fromCents(1050);

        $this->assertEquals(10.5, $money->toFloat());
    }

    public function testFormatToCents(): void
    {
        $money = Money::fromCents(1050);

        $this->assertEquals(1050, $money->toCents());
    }

    public function testFormatToString(): void
    {
        $money = Money::fromCents(1050);

        $this->assertEquals('R$ 10,50', $money->format());
    }
}