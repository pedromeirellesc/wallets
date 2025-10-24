<?php

namespace App\Services;

use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Transaction;
use App\ValueObjects\Money;
use PDO;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryContract $transactionRepository,
        private readonly WalletRepositoryContract $walletRepository,
        private readonly PDO $pdo,
    ) {
    }

    public function deposit(string $walletId, Money $amount, string $description = 'Deposit'): Transaction
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new \DomainException('Wallet not found');
        }

        return $this->executeInTransaction(function () use ($wallet, $walletId, $amount, $description) {
            $updatedWallet = $wallet->deposit($amount);
            $this->walletRepository->save($updatedWallet);

            $transaction = Transaction::createDeposit($walletId, $amount, $description);
            return $this->transactionRepository->save($transaction);
        });
    }

    public function withdraw(string $walletId, Money $amount, string $description = 'Withdrawal'): Transaction
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new \DomainException('Wallet not found');
        }

        return $this->executeInTransaction(function () use ($wallet, $walletId, $amount, $description) {
            $updatedWallet = $wallet->withdraw($amount);
            $this->walletRepository->save($updatedWallet);

            $transaction = Transaction::createWithdrawal($walletId, $amount, $description);
            return $this->transactionRepository->save($transaction);
        });
    }

    public function transfer(
        string $fromWalletId,
        string $toWalletId,
        Money $amount,
        string $description = 'Transfer',
    ): Transaction {
        $fromWallet = $this->walletRepository->findById($fromWalletId);
        $toWallet = $this->walletRepository->findById($toWalletId);

        if (!$fromWallet) {
            throw new \DomainException('Source wallet not found');
        }

        if (!$toWallet) {
            throw new \DomainException('Destination wallet not found');
        }

        $transaction = null;

        try {
            return $this->executeInTransaction(function () use ($fromWallet, $toWallet, $fromWalletId, $toWalletId, $amount, $description, &$transaction) {
                $transaction = Transaction::createTransfer($fromWalletId, $toWalletId, $amount, $description);
                $transaction = $this->transactionRepository->save($transaction);

                $updatedFromWallet = $fromWallet->withdraw($amount);
                $this->walletRepository->save($updatedFromWallet);

                $updatedToWallet = $toWallet->deposit($amount);
                $this->walletRepository->save($updatedToWallet);

                $completedTransaction = $transaction->complete();
                return $this->transactionRepository->save($completedTransaction);
            });
        } catch (\Exception $e) {
            if ($transaction && !empty($transaction->id())) {
                try {
                    $failedTransaction = $transaction->fail();
                    $this->transactionRepository->save($failedTransaction);
                } catch (\Exception $failException) {
                }
            }

            throw $e;
        }
    }


    private function executeInTransaction(callable $callback): mixed
    {
        $wasInTransaction = $this->pdo->inTransaction();

        if (!$wasInTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $callback();

            if (!$wasInTransaction) {
                $this->pdo->commit();
            }

            return $result;
        } catch (\Exception $e) {
            if (!$wasInTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
