<?php

namespace App\Models;

use App\ValueObjects\Money;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Wallet
{
    private function __construct(
        private readonly string $id,
        private readonly int $userId,
        private readonly Money $balance,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(int $userId): self
    {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            $userId,
            Money::zero(),
            $now,
            $now,
        );
    }

    public static function reconstitute(
        string $id,
        int $userId,
        Money $balance,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $userId, $balance, $createdAt, $updatedAt);
    }

    public function withId(string $id): self
    {
        return new self(
            $id,
            $this->userId,
            $this->balance,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function deposit(Money $amount): self
    {
        return new self(
            $this->id,
            $this->userId,
            $this->balance->add($amount),
            $this->createdAt,
            new DateTimeImmutable(),
        );
    }

    public function withdraw(Money $amount): self
    {
        if ($this->balance->isLessThan($amount)) {
            throw new \DomainException('Insufficient funds');
        }

        return new self(
            $this->id,
            $this->userId,
            $this->balance->subtract($amount),
            $this->createdAt,
            new DateTimeImmutable(),
        );
    }

    public function canWithdraw(Money $amount): bool
    {
        return $this->balance->isGreaterThanOrEqual($amount);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
