<?php

namespace Tests\Traits;

trait MoneyAssertions
{
    protected function assertMoneyEquals(int $expectedCents, $actual, string $message = ''): void
    {
        if (is_array($actual) && isset($actual['balance'])) {
            $this->assertEquals($expectedCents, $actual['balance'], $message);
        } else {
            $this->assertEquals($expectedCents, $actual, $message);
        }
    }

    protected function assertWalletBalance(string $walletId, int $expectedCents): void
    {
        $this->assertDatabaseHas('wallets', [
            'id' => $walletId,
            'balance' => $expectedCents,
        ]);
    }

    protected function assertTransactionAmount(string $transactionId, int $expectedCents): void
    {
        $this->assertDatabaseHas('transactions', [
            'id' => $transactionId,
            'amount' => $expectedCents,
        ]);
    }
}
