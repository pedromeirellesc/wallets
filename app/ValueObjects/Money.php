<?php

namespace App\ValueObjects;

class Money
{
    private function __construct(private readonly int $cents)
    {
    }

    public static function fromCents(int $cents): self
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative');
        }

        return new self($cents);
    }

    public static function fromFloat(float $amount): self
    {
        return self::fromCents((int) round($amount * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        $result = $this->cents - $other->cents;

        if ($result < 0) {
            throw new \InvalidArgumentException('Result cannot be negative');
        }

        return new self($result);
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function format(): string
    {
        return 'R$ ' . number_format($this->toFloat(), 2, ',', '.');
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }
}
