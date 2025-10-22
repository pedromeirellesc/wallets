<?php

namespace App\Infra\Persistence\Repositories;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use PDO;

class UserRepositoryMySql implements UserRepositoryContract
{

    public function __construct(private PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(User $user): User
    {
        $sql = "INSERT INTO users (name, email, password, type) VALUES (:name, :email, :password, :type)";
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':name', $user->name());
        $stmt->bindValue(':email', $user->email());
        $stmt->bindValue(':password', $user->password());
        $stmt->bindValue(':type', $user->type());

        $stmt->execute();

        $user->setId((int) $this->pdo->lastInsertId());

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT id, name, email, password, type FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch();

        return $user === false ? null : $this->hydrate($user);
    }

    public function hydrate(array $data): User
    {
        $user = new User($data['name'], $data['email'], $data['password'], UserType::from($data['type']));
        $user->setId((int) $data['id']);
        return $user;
    }
}