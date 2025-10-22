<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthenticationException;
use App\Services\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController
{

    public function __construct(public UserService $userService)
    {
    }

    public function register(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            $this->userService->register($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        return new JsonResponse(['message' => 'User registered successfully.'], 201);
    }

    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        $token = '';
        try {
            $token = $this->userService->login($data);
        } catch (AuthenticationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        return new JsonResponse([
            'message' => 'User logged successfully.',
            'token' => $token
        ], 200);
    }
}