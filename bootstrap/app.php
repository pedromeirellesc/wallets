<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Contracts\ExistsCheckerInterface;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Infra\Persistence\MySqlConnectionFactory;
use App\Infra\Persistence\Repositories\Contracts\TransactionRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Infra\Persistence\Repositories\Contracts\WalletRepositoryContract;
use App\Infra\Persistence\Repositories\TransactionRepositoryMySql;
use App\Infra\Persistence\Repositories\UserRepositoryMySql;
use App\Infra\Persistence\Repositories\WalletRepositoryMySql;
use App\Services\DatabaseExistsChecker;
use App\Validators\UserValidator;
use Dotenv\Dotenv;
use Laminas\Diactoros\Response\JsonResponse;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

$container = new Container();
$container->delegate(new ReflectionContainer(true));

$container->add(PDO::class, function () {
    $factory = new MySqlConnectionFactory();
    return $factory->createConnection();
})->setShared();

$container->add(ExistsCheckerInterface::class, DatabaseExistsChecker::class)
    ->addArgument(PDO::class);

$container->add(UserRepositoryContract::class, function () use ($container): UserRepositoryMySql {
    return new UserRepositoryMySql($container->get(PDO::class));
})->setShared();

$container->add(UserValidator::class)
    ->addArgument(ExistsCheckerInterface::class);

$container->add(WalletRepositoryContract::class, function () use ($container): WalletRepositoryContract {
    return new WalletRepositoryMySql($container->get(PDO::class));
})->setShared();

$container->add(TransactionRepositoryContract::class, function () use ($container): TransactionRepositoryContract {
    return new TransactionRepositoryMySql($container->get(PDO::class));
})->setShared();

$router = new Router();

$router->map('GET', '/status', function () {
    return new JsonResponse(['status' => 'ok']);
});

$router->map('POST', '/api/v1/users/register', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(UserController::class)->register($request);
});

$router->map('POST', '/api/v1/users/login', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(UserController::class)->login($request);
});

$router->map('GET', '/api/v1/wallets', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(WalletController::class)->index($request);
});

$router->map('GET', '/api/v1/wallets/{uuid}', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(WalletController::class)->show($request);
});

$router->map('POST', '/api/v1/transactions/deposit/{walletId}', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(TransactionController::class)->deposit($request);
});

$router->map('POST', '/api/v1/transactions/withdraw/{walletId}', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(TransactionController::class)->withdraw($request);
});

$router->map('POST', '/api/v1/transactions/transfer', function (ServerRequestInterface $request) use ($container): ResponseInterface {
    return $container->get(TransactionController::class)->transfer($request);
});

return [$container, $router];
