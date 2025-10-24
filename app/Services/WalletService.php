<?php

namespace App\Services;

use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Wallet;
use App\ValueObjects\Money;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryContract $walletRepository,
        private readonly UserRepositoryContract $userRepository,
    ) {
    }

    public function createWalletForUser(int $userId): Wallet
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new \DomainException("User not found");
        }

        $existingWallet = $this->walletRepository->findByUserId($userId);

        // Temporary
        if ($existingWallet) {
            throw new \DomainException("User already has a wallet");
        }

        $wallet = Wallet::create($userId);

        return $this->walletRepository->save($wallet);
    }

    public function findAll(): array
    {
        return $this->walletRepository->findAll();
    }

    public function findById(string $id): ?Wallet
    {
        return $this->walletRepository->findById($id);
    }

    public function deposit(string $walletId, Money $amount): Wallet
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new \DomainException("Wallet not found");
        }

        $updatedWallet = $wallet->deposit($amount);

        return $this->walletRepository->save($updatedWallet);
    }

    public function withdraw(string $walletId, Money $amount): Wallet
    {
        $wallet = $this->walletRepository->findById($walletId);

        if (!$wallet) {
            throw new \DomainException("Wallet not found");
        }

        $updatedWallet = $wallet->withdraw($amount);

        return $this->walletRepository->save($updatedWallet);
    }
}
