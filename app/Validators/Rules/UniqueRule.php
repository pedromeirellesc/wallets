<?php

namespace App\Validators\Rules;

use App\Contracts\ExistsCheckerInterface;

class UniqueRule
{
    public function __construct(
        private readonly ExistsCheckerInterface $existsChecker,
    ) {
    }

    public function validate(string $table, string $column, mixed $value): bool
    {
        return !$this->existsChecker->exists($table, $column, $value);
    }
}
