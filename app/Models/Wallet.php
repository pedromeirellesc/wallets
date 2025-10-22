<?php

namespace App\Models;

use App\ValueObjects\Money;

class Wallet
{
    private ?string $id = null;
    private int $userId;
    private Money $balance;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(int $userId, Money $balance = null)
    {
        $this->userId = $userId;
        $this->balance = $balance ?? Money::zero();
    }

    public function id(): ?string
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


    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function deposit(Money $amount): void
    {
        if (!$amount->isPositive()) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        $this->balance = $this->balance->add($amount);
    }

    public function withdraw(Money $amount): void
    {
        if (!$amount->isPositive()) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        if ($this->balance->isLessThan($amount)) {
            throw new \InvalidArgumentException('Insufficient funds');
        }

        $this->balance = $this->balance->subtract($amount);
    }
}