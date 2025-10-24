<?php

namespace App\Infra\Persistence\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryContract
{
    public function save(User $user): User;
    public function insert(User $user): User;
    public function update(User $user): User;
    public function findByEmail(string $email): ?User;
    public function findById(int $id): ?User;
    public function hydrate(array $data): User;
}
