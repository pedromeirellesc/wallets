<?php

namespace App\Infra\Persistence\Repositories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Models\Transaction;
use App\ValueObjects\Money;
use DateTimeImmutable;
use PDO;

class TransactionRepositoryMySql implements TransactionRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Transaction $transaction): Transaction
    {
        $exists = $this->findById($transaction->id()) !== null;

        if (!$exists) {
            return $this->insert($transaction);
        }

        return $this->update($transaction);
    }

    public function insert(Transaction $transaction): Transaction
    {
        $sql = "INSERT INTO transactions 
                (id, type, from_wallet_id, to_wallet_id, amount, description, status, created_at, updated_at) 
                VALUES (:id, :type, :from_wallet_id, :to_wallet_id, :amount, :description, :status, :created_at, :updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $transaction->id(),
            ':type' => $transaction->type()->value,
            ':from_wallet_id' => $transaction->fromWalletId(),
            ':to_wallet_id' => $transaction->toWalletId(),
            ':amount' => $transaction->amount()->toCents(),
            ':description' => $transaction->description(),
            ':status' => $transaction->status()->value,
            ':created_at' => $transaction->createdAt()->format('Y-m-d H:i:s'),
            ':updated_at' => $transaction->updatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $transaction->withId($transaction->id());
    }

    public function update(Transaction $transaction): Transaction
    {
        $sql = "UPDATE transactions 
                SET status = :status, updated_at = :updated_at 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $transaction->id(),
            ':status' => $transaction->status()->value,
            ':updated_at' => $transaction->updatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $transaction;
    }

    public function findById(string $id): ?Transaction
    {
        $sql = "SELECT * FROM transactions WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function hydrate(array $data): Transaction
    {
        return Transaction::reconstitute(
            $data['id'],
            TransactionType::from($data['type']),
            $data['from_wallet_id'],
            $data['to_wallet_id'],
            Money::fromCents((int) $data['amount']),
            $data['description'],
            TransactionStatus::from($data['status']),
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );
    }
}
