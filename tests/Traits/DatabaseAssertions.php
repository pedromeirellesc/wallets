<?php

declare(strict_types=1);

namespace Tests\Traits;

trait DatabaseAssertions
{
    public function assertDatabaseHas(string $table, array $criteria): void
    {
        $pdo = $this->getConnection();

        $whereClauses = [];
        foreach (array_keys($criteria) as $column) {
            $whereClauses[] = "{$column} = :{$column}";
        }
        $whereSql = implode(' AND ', $whereClauses);

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$whereSql}";
        $stmt = $pdo->prepare($sql);

        foreach ($criteria as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }

        $stmt->execute();
        $result = $stmt->fetch();
        $count = (int) ($result['count'] ?? 0);

        $criteriaJson = json_encode($criteria, JSON_PRETTY_PRINT);
        $this->assertGreaterThan(
            0,
            $count,
            "Failed asserting that the table '{$table}' has a row matching the criteria:\n{$criteriaJson}",
        );
    }

    public function assertDatabaseMissing(string $table, array $criteria): void
    {
        $pdo = $this->getConnection();

        $whereClauses = [];
        foreach (array_keys($criteria) as $column) {
            $whereClauses[] = "{$column} = :{$column}";
        }
        $whereSql = implode(' AND ', $whereClauses);

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$whereSql}";
        $stmt = $pdo->prepare($sql);

        foreach ($criteria as $column => $value) {
            $stmt->bindValue(":{$column}", $value);
        }

        $stmt->execute();
        $result = $stmt->fetch();
        $count = (int) ($result['count'] ?? 0);

        $criteriaJson = json_encode($criteria, JSON_PRETTY_PRINT);
        $this->assertLessThan(
            1,
            $count,
            "Failed asserting that the table '{$table}' does not have a row matching the criteria:\n{$criteriaJson}",
        );
    }
}
