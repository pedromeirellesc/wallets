<?php

namespace App\Contracts;

interface ExistsCheckerInterface
{
    public function exists(string $table, string $column, mixed $value): bool;
}
