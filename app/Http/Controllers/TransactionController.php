<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\ValueObjects\Money;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TransactionController
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    public function deposit(ServerRequestInterface $request): ResponseInterface
    {
        $walletId = $request->getAttribute('walletId');
        $data = $request->getParsedBody();

        try {
            $amount = $this->parseMoneyFromRequest($data['amount']);
            $description = $data['description'] ?? 'Deposit';

            $transaction = $this->transactionService->deposit($walletId, $amount, $description);

            return new JsonResponse([
                'message' => 'Deposit completed successfully.',
                'data' => (new TransactionResource($transaction))->toArray(),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\DomainException $e) {
            return new JsonResponse([
                'error' => 'Operation failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Deposit failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function withdraw(ServerRequestInterface $request): ResponseInterface
    {
        $walletId = $request->getAttribute('walletId');
        $data = $request->getParsedBody();

        try {
            $amount = $this->parseMoneyFromRequest($data['amount']);
            $description = $data['description'] ?? 'Withdrawal';

            $transaction = $this->transactionService->withdraw($walletId, $amount, $description);

            return new JsonResponse([
                'message' => 'Withdrawal completed successfully.',
                'data' => (new TransactionResource($transaction))->toArray(),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\DomainException $e) {
            return new JsonResponse([
                'error' => 'Operation failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Withdrawal failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function transfer(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        try {
            $amount = $this->parseMoneyFromRequest($data['amount']);
            $description = $data['description'] ?? 'Transfer';

            $transaction = $this->transactionService->transfer(
                $data['from_wallet_id'],
                $data['to_wallet_id'],
                $amount,
                $description,
            );

            return new JsonResponse([
                'message' => 'Transfer completed successfully.',
                'data' => (new TransactionResource($transaction))->toArray(),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\DomainException $e) {
            return new JsonResponse([
                'error' => 'Transfer failed',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Transfer failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function parseMoneyFromRequest(mixed $amount): Money
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        return Money::fromFloat((float)$amount);
    }
}
