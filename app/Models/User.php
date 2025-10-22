<?php

namespace App\Models;

use App\Enums\UserType;

class User
{
    private int $id;
    private string $name;
    private string $email;
    private string $password;
    private UserType $type;

    public function __construct(string $name, string $email, string $password, UserType $type)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->type = $type;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function type(): string
    {
        return $this->type->value;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}