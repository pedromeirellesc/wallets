<?php

namespace App\Infra\Persistence\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryContract
{
    public function save(Transaction $transaction): Transaction;
}
