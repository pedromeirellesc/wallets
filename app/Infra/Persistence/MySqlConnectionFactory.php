<?php

namespace App\Infra\Persistence;

use PDO;

class MySqlConnectionFactory
{
    public function createConnection(): PDO
    {
        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
        $dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
        $dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $dbUser, $dbPass, $options);
    }
}
