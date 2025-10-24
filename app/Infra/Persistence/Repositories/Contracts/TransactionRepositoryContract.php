<?php

namespace App\Infra\Persistence\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryContract
{
    public function save(Transaction $transaction): Transaction;
    public function insert(Transaction $transaction): Transaction;
    public function update(Transaction $transaction): Transaction;
    public function findById(string $id): ?Transaction;
    public function hydrate(array $data): Transaction;
}
