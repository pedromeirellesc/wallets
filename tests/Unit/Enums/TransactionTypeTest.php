<?php

namespace Tests\Unit\Enums;

use App\Enums\TransactionType;
use PHPUnit\Framework\TestCase;

final class TransactionTypeTest extends TestCase
{
    public function testEnumValues()
    {
        $this->assertEquals('DEPOSIT', TransactionType::DEPOSIT->value);
        $this->assertEquals('WITHDRAW', TransactionType::WITHDRAW->value);
        $this->assertEquals('TRANSFER', TransactionType::TRANSFER->value);
    }

    public function testIsDeposit(): void
    {
        $this->assertTrue(TransactionType::DEPOSIT->isDeposit());
        $this->assertFalse(TransactionType::WITHDRAW->isDeposit());
        $this->assertFalse(TransactionType::TRANSFER->isDeposit());
    }

    public function testIsWithdraw(): void
    {
        $this->assertFalse(TransactionType::DEPOSIT->isWithdraw());
        $this->assertTrue(TransactionType::WITHDRAW->isWithdraw());
        $this->assertFalse(TransactionType::TRANSFER->isWithdraw());
    }

    public function testIsTransfer(): void
    {
        $this->assertFalse(TransactionType::DEPOSIT->isTransfer());
        $this->assertFalse(TransactionType::WITHDRAW->isTransfer());
        $this->assertTrue(TransactionType::TRANSFER->isTransfer());
    }
}
