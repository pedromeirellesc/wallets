<?php

namespace App\Infra\Persistence\Repositories;

use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Models\User;
use App\Models\Wallet;
use App\ValueObjects\Money;
use PDO;
use Ramsey\Uuid\Uuid;

class WalletRepositoryMySql implements WalletRepositoryContract
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Wallet $wallet): Wallet
    {
        if (!$wallet->id()) {
            $wallet->setId(Uuid::uuid4()->toString());

            $this->pdo->prepare('INSERT INTO wallets (id, user_id, balance) VALUES (:id,:user_id, :balance)')
                ->execute([
                    'id' => $wallet->id(),
                    'user_id' => $wallet->userId(),
                    'balance' => $wallet->balance()->toCents(),
                ]);
        }

        return $wallet;
    }

    public function update(Wallet $wallet): Wallet
    {
        $this->pdo->prepare('UPDATE wallets SET balance = :balance WHERE id = :id')
            ->execute([
                'id' => $wallet->id(),
                'balance' => $wallet->balance()->toCents(),
            ]);

        return $wallet;
    }

    public function findById(string $id): ?Wallet
    {
        $stmt = $this->pdo->prepare('SELECT * FROM wallets WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $this->hydrate($result) : null;
    }

    public function findAll(): array
    {
        return $this->pdo->query('SELECT * FROM wallets')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hydrate(array $data): Wallet
    {
        $wallet = new Wallet($data['user_id'], new Money($data['balance']));
        $wallet->setId($data['id']);
        return $wallet;
    }
}
