<?php

namespace App\Infra\Persistence\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryContract
{
    public function save(Wallet $wallet): Wallet;
    public function insert(Wallet $wallet): Wallet;
    public function update(Wallet $wallet): Wallet;
    public function findById(string $id): ?Wallet;
    public function findByUserId(int $userId): ?Wallet;
    public function findAll(): array;
    public function hydrate(array $data): Wallet;
}
