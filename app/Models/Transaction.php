<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\ValueObjects\Money;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class Transaction
{
    private function __construct(
        private readonly string $id,
        private readonly TransactionType $type,
        private readonly ?string $fromWalletId,
        private readonly ?string $toWalletId,
        private readonly Money $amount,
        private readonly string $description,
        private readonly TransactionStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private readonly DateTimeImmutable $updatedAt,
    ) {
        $this->validateTransaction();
    }

    private function validateTransaction(): void
    {
        if (!$this->amount->isPositive()) {
            throw new \InvalidArgumentException('Transaction amount must be positive');
        }

        if ($this->type === TransactionType::DEPOSIT && $this->toWalletId === null) {
            throw new \InvalidArgumentException('Deposit must have a destination wallet');
        }

        if ($this->type === TransactionType::WITHDRAW && $this->fromWalletId === null) {
            throw new \InvalidArgumentException('Withdrawal must have a source wallet');
        }

        if ($this->type === TransactionType::TRANSFER) {
            if ($this->fromWalletId === null || $this->toWalletId === null) {
                throw new \InvalidArgumentException('Transfer must have both source and destination wallets');
            }

            if ($this->fromWalletId === $this->toWalletId) {
                throw new \InvalidArgumentException('Cannot transfer to the same wallet');
            }
        }
    }

    public static function createDeposit(
        string $toWalletId,
        Money $amount,
        string $description = 'Deposit to wallet',
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            TransactionType::DEPOSIT,
            null,
            $toWalletId,
            $amount,
            $description,
            TransactionStatus::COMPLETED,
            $now,
            $now,
        );
    }

    public static function createWithdrawal(
        string $fromWalletId,
        Money $amount,
        string $description = 'Withdrawal from wallet',
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            TransactionType::WITHDRAW,
            $fromWalletId,
            null,
            $amount,
            $description,
            TransactionStatus::COMPLETED,
            $now,
            $now,
        );
    }

    public static function createTransfer(
        string $fromWalletId,
        string $toWalletId,
        Money $amount,
        string $description = 'Transfer between wallets',
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            Uuid::uuid4()->toString(),
            TransactionType::TRANSFER,
            $fromWalletId,
            $toWalletId,
            $amount,
            $description,
            TransactionStatus::PENDING,
            $now,
            $now,
        );
    }

    public static function reconstitute(
        string $id,
        TransactionType $type,
        ?string $fromWalletId,
        ?string $toWalletId,
        Money $amount,
        string $description,
        TransactionStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $type,
            $fromWalletId,
            $toWalletId,
            $amount,
            $description,
            $status,
            $createdAt,
            $updatedAt,
        );
    }

    public function withId(string $id): self
    {
        return new self(
            $id,
            $this->type,
            $this->fromWalletId,
            $this->toWalletId,
            $this->amount,
            $this->description,
            $this->status,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function complete(): self
    {
        if ($this->status === TransactionStatus::COMPLETED) {
            throw new \DomainException('Transaction is already completed');
        }

        if ($this->status === TransactionStatus::FAILED) {
            throw new \DomainException('Cannot complete a failed transaction');
        }

        return new self(
            $this->id,
            $this->type,
            $this->fromWalletId,
            $this->toWalletId,
            $this->amount,
            $this->description,
            TransactionStatus::COMPLETED,
            $this->createdAt,
            new DateTimeImmutable(),
        );
    }

    public function fail(): self
    {
        if ($this->status === TransactionStatus::COMPLETED) {
            throw new \DomainException('Cannot fail a completed transaction');
        }

        return new self(
            $this->id,
            $this->type,
            $this->fromWalletId,
            $this->toWalletId,
            $this->amount,
            $this->description,
            TransactionStatus::FAILED,
            $this->createdAt,
            new DateTimeImmutable(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): TransactionType
    {
        return $this->type;
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

    public function status(): TransactionStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED;
    }
}
