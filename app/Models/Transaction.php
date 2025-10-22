<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\ValueObjects\Money;

class Transaction
{
    private ?string $id = null;
    private TransactionType $type;
    private ?string $fromWalletId;
    private ?string $toWalletId;
    private Money $amount;
    private string $description;
    private TransactionStatus $status;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    private function __construct(
        TransactionType $type,
        ?string $fromWalletId,
        ?string $toWalletId,
        Money $amount,
        string $description = '',
        TransactionStatus $status = TransactionStatus::PENDING
    ) {
        if (!$amount->isPositive()) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        $this->type = $type;
        $this->fromWalletId = $fromWalletId;
        $this->toWalletId = $toWalletId;
        $this->amount = $amount;
        $this->description = $description;
        $this->status = $status;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function type(): string
    {
        return $this->type->value;
    }

    public function fromWalletId(): ?string
    {
        return $this->fromWalletId;
    }

    public function toWalletId(): ?string
    {
        return $this->toWalletId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): string
    {
        return $this->status->value;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public static function deposit(string $toWalletId, Money $amount, ?string $description = 'Deposit to wallet'): self
    {
        return new self(TransactionType::DEPOSIT, null, $toWalletId, $amount, $description, TransactionStatus::COMPLETED);
    }

    public static function withdraw(string $fromWalletId, Money $amount, ?string $description = 'Withdraw from wallet'): self
    {
        return new self(TransactionType::WITHDRAW, $fromWalletId, null, $amount, $description, TransactionStatus::COMPLETED);
    }

    public static function transfer(string $fromWalletId, string $toWalletId, Money $amount): self
    {
        if ($fromWalletId === $toWalletId) {
            throw new \InvalidArgumentException('Cannot transfer to the same wallet');
        }

        return new self(TransactionType::TRANSFER, $fromWalletId, $toWalletId, $amount);
    }
}