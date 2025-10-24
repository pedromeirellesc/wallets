<?php

namespace App\Services;

use App\Enums\UserType;
use App\Exceptions\AuthenticationException;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use App\Services\Auth\JwtTokenGenerator;
use App\Services\Auth\PasswordHasher;
use App\Validators\UserValidator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryContract $userRepository,
        private readonly WalletService $walletService,
        private readonly UserValidator $userValidator,
        private readonly PasswordHasher $passwordHasher,
        private readonly JwtTokenGenerator $tokenGenerator,
    ) {
    }

    public function register(array $data): User
    {
        $validatedData = $this->userValidator->validateCreate($data);

        $user = User::create(
            $validatedData['name'],
            $validatedData['email'],
            $this->passwordHasher->hash($validatedData['password']),
            UserType::from($validatedData['type']),
        );

        $user = $this->userRepository->save($user);
        $this->walletService->createWalletForUser($user->id());

        return $user;
    }

    public function login(string $email, string $password): string
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$this->passwordHasher->verify($password, $user->password())) {
            throw new AuthenticationException("Invalid credentials.");
        }

        return $this->tokenGenerator->generate($user);
    }
}
