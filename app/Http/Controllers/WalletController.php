<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WalletController
{

    public function __construct(public WalletService $walletService)
    {
    }

    public function index(): ResponseInterface
    {
        $wallets = $this->walletService->index();

        return new JsonResponse([
            'data' => $wallets //ToDo: return a collection with resource
        ]);
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('uuid');
        $wallet = $this->walletService->show($id);

        if (!$wallet) {
            return new JsonResponse([
                'error' => 'Wallet not found'
            ], 404);
        }

        return new JsonResponse([
            'data' => (new WalletResource($wallet))->toArray()
        ]);
    }
}