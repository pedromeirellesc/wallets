<?php

namespace App\Models;

use App\Enums\UserType;

class User
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $email,
        private readonly string $password,
        private readonly UserType $type,
    ) {
    }

    public static function create(
        string $name,
        string $email,
        string $password,
        UserType $type,
    ): self {
        return new self(0, $name, $email, $password, $type);
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $email,
        string $password,
        UserType $type,
    ): self {
        return new self($id, $name, $email, $password, $type);
    }

    public function withId(int $id): self
    {
        return new self($id, $this->name, $this->email, $this->password, $this->type);
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

    public function type(): UserType
    {
        return $this->type;
    }
}
