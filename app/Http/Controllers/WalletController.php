<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WalletController
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {
    }

    public function index(): ResponseInterface
    {
        try {
            $wallets = $this->walletService->findAll();

            return new JsonResponse([
                'data' => array_map(
                    fn ($wallet) => (new WalletResource($wallet))->toArray(),
                    $wallets,
                ),
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to retrieve wallets',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('uuid');

        try {
            $wallet = $this->walletService->findById($id);

            if (!$wallet) {
                return new JsonResponse([
                    'error' => 'Wallet not found',
                ], 404);
            }

            return new JsonResponse([
                'data' => (new WalletResource($wallet))->toArray(),
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to retrieve wallet',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
