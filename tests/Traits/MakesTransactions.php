<?php

namespace Tests\Traits;

trait MakesTransactions
{
    protected function makeDeposit(string $walletId, float $amount): array
    {
        $response = $this->postJson("/api/v1/transactions/deposit/{$walletId}", [
            'amount' => $amount,
        ]);

        return [
            'response' => $response,
            'data' => json_decode($response->getBody()->getContents(), true),
        ];
    }

    protected function makeWithdrawal(string $walletId, float $amount): array
    {
        $response = $this->postJson("/api/v1/transactions/withdraw/{$walletId}", [
            'amount' => $amount,
        ]);

        return [
            'response' => $response,
            'data' => json_decode($response->getBody()->getContents(), true),
        ];
    }

    protected function makeTransfer(string $fromWalletId, string $toWalletId, float $amount): array
    {
        $response = $this->postJson('/api/v1/transactions/transfer', [
            'from_wallet_id' => $fromWalletId,
            'to_wallet_id' => $toWalletId,
            'amount' => $amount,
        ]);

        return [
            'response' => $response,
            'data' => json_decode($response->getBody()->getContents(), true),
        ];
    }
}
