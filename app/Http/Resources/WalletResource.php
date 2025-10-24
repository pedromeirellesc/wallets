<?php

namespace App\Http\Resources;

use App\Models\Wallet;

class WalletResource
{
    public function __construct(private readonly Wallet $wallet)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->wallet->id(),
            'user_id' => $this->wallet->userId(),
            'balance' => [
                'amount' => $this->wallet->balance()->toFloat(),
                'formatted' => $this->wallet->balance()->format(),
                'cents' => $this->wallet->balance()->toCents(),
            ],
            'created_at' => $this->wallet->createdAt()->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->wallet->updatedAt()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
