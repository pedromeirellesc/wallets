<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Services\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            $user = $this->userService->register($data);

            return new JsonResponse([
                'message' => 'User registered successfully.',
                'user_id' => $user->id(),
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'error' => 'Validation failed.',
                'details' => $e->getErrors(),
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Registration failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            $token = $this->userService->login($data['email'] ?? '', $data['password'] ?? '');

            return new JsonResponse([
                'message' => 'User logged successfully.',
                'token' => $token,
            ], 200);
        } catch (AuthenticationException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Login failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
