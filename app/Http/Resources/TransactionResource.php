<?php

namespace App\Http\Resources;

use App\Models\Transaction;

class TransactionResource
{
    public function __construct(private readonly Transaction $transaction)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->transaction->id(),
            'type' => $this->transaction->type()->value,
            'from_wallet_id' => $this->transaction->fromWalletId(),
            'to_wallet_id' => $this->transaction->toWalletId(),
            'amount' => [
                'cents' => $this->transaction->amount()->toCents(),
                'formatted' => $this->transaction->amount()->format(),
                'value' => $this->transaction->amount()->toFloat(),
            ],
            'description' => $this->transaction->description(),
            'status' => $this->transaction->status()->value,
            'created_at' => $this->transaction->createdAt()->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $this->transaction->updatedAt()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
