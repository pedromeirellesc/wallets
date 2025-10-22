<?php

namespace App\Http\Resources;

use App\Models\Wallet;

class WalletResource
{
    public function __construct(private Wallet $wallet)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->wallet->id(),
            'user_id' => $this->wallet->userId(),
            'balance' => $this->wallet->balance()->toFloat(),
            'balanceFormatted' => $this->wallet->balance()->format(),
            'createdAt' => $this->wallet->createdAt(),
            'updatedAt' => $this->wallet->updatedAt(),
        ];
    }
}