<?php

namespace App\Infra\Persistence\Repositories;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use PDO;

class UserRepositoryMySql implements UserRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(User $user): User
    {
        if ($user->id() === 0) {
            return $this->insert($user);
        }

        return $this->update($user);
    }

    public function insert(User $user): User
    {
        $sql = "INSERT INTO users (name, email, password, type) VALUES (:name, :email, :password, :type)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $user->name(),
            ':email' => $user->email(),
            ':password' => $user->password(),
            ':type' => $user->type()->value,
        ]);

        return $user->withId((int) $this->pdo->lastInsertId());
    }

    public function update(User $user): User
    {
        $sql = "UPDATE users SET name = :name, email = :email, password = :password, type = :type WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $user->id(),
            ':name' => $user->name(),
            ':email' => $user->email(),
            ':password' => $user->password(),
            ':type' => $user->type()->value,
        ]);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT id, name, email, password, type FROM users WHERE email = :email";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findById(int $id): ?User
    {
        $sql = "SELECT id, name, email, password, type FROM users WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function hydrate(array $data): User
    {
        return User::reconstitute(
            (int) $data['id'],
            $data['name'],
            $data['email'],
            $data['password'],
            UserType::from($data['type']),
        );
    }
}
