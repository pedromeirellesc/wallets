<?php

namespace App\Infra\Persistence\Repositories;

use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Models\Transaction;
use PDO;
use Ramsey\Uuid\Uuid;

class TransactionRepositoryMySql implements TransactionRepositoryContract
{

    public function __construct(private PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Transaction $transaction): Transaction
    {
        if (!$transaction->id()) {
            $transaction->setId(Uuid::uuid4()->toString());

            $this->pdo->prepare(
                'INSERT INTO transactions (id, type, from_wallet_id, to_wallet_id, amount, description, status) VALUES (:id, :type, :from_wallet_id, :to_wallet_id, :amount, :description, :status)'
            )
                ->execute([
                    'id' => $transaction->id(),
                    'type' => $transaction->type(),
                    'from_wallet_id' => $transaction->fromWalletId(),
                    'to_wallet_id' => $transaction->toWalletId(),
                    'amount' => $transaction->amount()->toCents(),
                    'description' => $transaction->description(),
                    'status' => $transaction->status(),
                ]);
        }

        return $transaction;
    }
}
