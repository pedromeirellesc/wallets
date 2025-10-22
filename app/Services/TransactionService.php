<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Http\Resources\TransactionResource;
use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Transaction;
use App\Validators\TransactionValidator;
use App\ValueObjects\Money;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryContract $transactionRepository,
        private readonly WalletRepositoryContract $walletRepository,
        private readonly TransactionValidator $transactionValidator,
    ) {}

    public function deposit(string $walletId, Money $amount): Transaction
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new ValidationException(['walletId' => ['Wallet not found.']]);
        }

        $wallet->deposit($amount);

        $this->walletRepository->update($wallet);
        $transaction = Transaction::deposit($walletId, $amount);
        $this->transactionValidator->validateCreate((new TransactionResource($transaction))->toArray());
        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function withdraw(string $walletId, Money $amount): Transaction
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new ValidationException(['walletId' => ['Wallet not found.']]);
        }

        $wallet->withdraw($amount);

        $this->walletRepository->update($wallet);
        $transaction = Transaction::withdraw($walletId, $amount);
        $this->transactionValidator->validateCreate((new TransactionResource($transaction))->toArray());
        $this->transactionRepository->save($transaction);

        return $transaction;
    }
}