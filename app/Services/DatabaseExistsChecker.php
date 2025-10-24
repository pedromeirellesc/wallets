<?php

namespace App\Services;

use App\Contracts\ExistsCheckerInterface;
use PDO;

class DatabaseExistsChecker implements ExistsCheckerInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function exists(string $table, string $column, mixed $value): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
