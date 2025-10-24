<?php

declare(strict_types=1);

namespace Tests;

use App\Infra\Persistence\MySqlConnectionFactory;
use Laminas\Diactoros\ServerRequest;
use League\Container\Container;
use League\Route\Router;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;

abstract class AppTestCase extends BaseTestCase
{
    protected Container $container;
    protected Router $router;
    protected static ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->container, $this->router] = require dirname(__DIR__) . '/bootstrap/app.php';

        $this->container->extend(PDO::class)->setConcrete(fn () => self::getConnection());

        $this->ensureNoActiveTransaction();
        $this->cleanDatabase();
    }

    protected function cleanDatabase(): void
    {
        $pdo = self::getConnection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('TRUNCATE TABLE transactions');
        $pdo->exec('TRUNCATE TABLE wallets');
        $pdo->exec('TRUNCATE TABLE users');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function tearDown(): void
    {
        $this->ensureNoActiveTransaction();
        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::initializeTestDatabase();
    }

    protected static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $factory = new MySqlConnectionFactory();
            self::$pdo = $factory->createConnection();
        }
        return self::$pdo;
    }

    private static function initializeTestDatabase(): void
    {
        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        $dbName = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE');
        $dbUser = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME');
        $dbPass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

        $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`;");
        $pdo->exec("USE `{$dbName}`;");

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if ($tables) {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE `{$table}`;");
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }

        self::$pdo = null;
        $testDbPdo = self::getConnection();
        $schemaSql = file_get_contents(dirname(__DIR__) . '/database/create.sql');
        $testDbPdo->exec($schemaSql);
    }

    private function ensureNoActiveTransaction(): void
    {
        while (self::getConnection()->inTransaction()) {
            self::getConnection()->rollBack();
        }
    }

    public function postJson(string $uri, array $data): ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json'];

        $request = new ServerRequest(
            serverParams: [],
            uploadedFiles: [],
            uri: $uri,
            method: 'POST',
            body: 'php://input',
            headers: $headers,
        );
        $request = $request->withParsedBody($data);

        return $this->router->dispatch($request);
    }

    public function get(string $uri): ResponseInterface
    {
        $headers = [];

        $request = new ServerRequest(
            serverParams: [],
            uploadedFiles: [],
            uri: $uri,
            method: 'GET',
            headers: $headers,
        );

        return $this->router->dispatch($request);
    }
}
