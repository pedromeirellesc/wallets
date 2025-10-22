<?php

namespace App\Http\Resources;

use App\Models\Transaction;

class TransactionResource
{
    public function __construct(private Transaction $transaction)
    {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->transaction->id(),
            'type' => $this->transaction->type(),
            'from_wallet_id' => $this->transaction->fromWalletId(),
            'to_wallet_id' => $this->transaction->toWalletId(),
            'amount' => $this->transaction->amount()->toCents(),
            'amountFormatted' => $this->transaction->amount()->format(),
            'description' => $this->transaction->description(),
            'status' => $this->transaction->status(),
            'created_at' => $this->transaction->createdAt(),
            'updated_at' => $this->transaction->updatedAt(),
        ];
    }
}
