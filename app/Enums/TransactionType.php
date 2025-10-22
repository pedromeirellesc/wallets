<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'DEPOSIT';
    case WITHDRAW = 'WITHDRAW';
    case TRANSFER = 'TRANSFER';

    public function isDeposit(): bool
    {
        return $this === self::DEPOSIT;
    }

    public function isWithdraw(): bool
    {
        return $this === self::WITHDRAW;
    }

    public function isTransfer(): bool
    {
        return $this === self::TRANSFER;
    }
}
