<?php

namespace App\Services;

use App\Http\Resources\WalletResource;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Wallet;
use App\Validators\WalletValidator;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryContract $walletRepository,
        private readonly WalletValidator $walletValidator,
    ) {}

    public function createWalletForUser(int $userId): void
    {
        $wallet = new Wallet($userId);

        $this->walletValidator->validateCreate((new WalletResource($wallet))->toArray());

        $this->walletRepository->save($wallet);
    }

    public function index(): array
    {
        return $this->walletRepository->findAll();
    }

    public function show(string $id): ?Wallet {
        return $this->walletRepository->findById($id);
    }
}
