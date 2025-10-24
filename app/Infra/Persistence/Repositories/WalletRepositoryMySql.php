<?php

namespace App\Infra\Persistence\Repositories;

use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\Wallet;
use App\ValueObjects\Money;
use DateTimeImmutable;
use PDO;

class WalletRepositoryMySql implements WalletRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Wallet $wallet): Wallet
    {
        $exists = $this->findById($wallet->id()) !== null;

        if (!$exists) {
            return $this->insert($wallet);
        }

        return $this->update($wallet);
    }

    public function insert(Wallet $wallet): Wallet
    {
        $sql = "INSERT INTO wallets (id, user_id, balance, created_at, updated_at) VALUES (:id, :user_id, :balance, :created_at, :updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $wallet->id(),
            ':user_id' => $wallet->userId(),
            ':balance' => $wallet->balance()->toCents(),
            ':created_at' => $wallet->createdAt()->format('Y-m-d H:i:s'),
            ':updated_at' => $wallet->updatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $wallet;
    }

    public function update(Wallet $wallet): Wallet
    {
        $sql = "UPDATE wallets SET balance = :balance, updated_at = :updated_at WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $wallet->id(),
            ':balance' => $wallet->balance()->toCents(),
            ':updated_at' => $wallet->updatedAt()->format('Y-m-d H:i:s'),
        ]);

        return $wallet;
    }

    public function findById(string $id): ?Wallet
    {
        $sql = "SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findByUserId(int $userId): ?Wallet
    {
        $sql = "SELECT id, user_id, balance, created_at, updated_at FROM wallets WHERE user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT id, user_id, balance, created_at, updated_at FROM wallets";

        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn ($data) => $this->hydrate($data), $results);
    }

    public function hydrate(array $data): Wallet
    {
        return Wallet::reconstitute(
            $data['id'],
            (int) $data['user_id'],
            Money::fromCents((int) $data['balance']),
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );
    }
}
