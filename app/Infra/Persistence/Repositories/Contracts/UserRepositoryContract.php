<?php

namespace App\Infra\Persistence\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryContract
{
    public function save(User $user): User;

    public function findByEmail(string $email): ?User;
}