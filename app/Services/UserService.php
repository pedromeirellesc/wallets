<?php

namespace App\Services;

use App\Enums\UserType;
use App\Exceptions\AuthenticationException;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use App\Validators\UserValidator;
use Firebase\JWT\JWT;

class UserService
{

    public function __construct(private readonly UserRepositoryContract $userRepository, private readonly WalletService $walletService, private readonly UserValidator $userValidator)
    {
    }

    public function register(array $data): void
    {
        $this->userValidator->validateCreate($data);

        $user = new User($data['name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT), UserType::from($data['type']));

        $user = $this->userRepository->save($user);

        $this->walletService->createWalletForUser($user->id());
    }

    public function login(array $data): string
    {
        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !password_verify($data['password'], $user->password())) {
            throw new AuthenticationException("Invalid credentials.");
        }

        $secretKey = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
        $issueTime = time();
        $expirationTime = $issueTime + 1800;

        $payload = [
            'iss' => 'your-app-issuer', // ToDo: Change app issue
            'iat' => time(),
            'exp' => $expirationTime,
            'sub' => $user->id(),
        ];

        return JWT::encode($payload, $secretKey, 'HS256');
    }
}