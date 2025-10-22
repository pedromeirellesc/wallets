<?php

namespace App\ValueObjects;

class Money
{
    private int $amountInCents;

    public function __construct(int $amountInCents)
    {
        $this->amountInCents = $amountInCents;
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromFloat(float $amount): self
    {
        return new self((int)round($amount * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): self
    {
        return new self($this->amountInCents + $other->amountInCents);
    }

    public function subtract(Money $other): self
    {
        return new self($this->amountInCents - $other->amountInCents);
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->amountInCents > $other->amountInCents;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->amountInCents < $other->amountInCents;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents;
    }

    public function isPositive(): bool
    {
        return $this->amountInCents > 0;
    }

    public function isNegative(): bool
    {
        return $this->amountInCents < 0;
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function toFloat(): float
    {
        return $this->amountInCents / 100;
    }

    public function toCents(): int
    {
        return $this->amountInCents;
    }

    public function format(): string
    {
        return 'R$ ' . number_format($this->toFloat(), 2, ',', '.');
    }
}
