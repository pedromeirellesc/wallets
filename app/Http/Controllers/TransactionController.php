<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\ValueObjects\Money;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TransactionController
{

    public function __construct(private readonly TransactionService $transactionService)
    {
    }

    public function deposit(ServerRequestInterface $request): ResponseInterface
    {
        $walletId = $request->getAttribute('walletId');
        $data = $request->getParsedBody();
        $amount = new Money($data['amount']);

        try {
            $transaction = $this->transactionService->deposit($walletId, $amount);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'message' => 'Deposit failed.',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Deposit failed.',
                'errors' => $e->getMessage()
            ], 422);
        }

        return new JsonResponse([
            'message' => 'Deposit completed successfully.',
            'data' => (new TransactionResource($transaction))->toArray()
        ], 201);
    }

    public function withdraw(ServerRequestInterface $request): ResponseInterface
    {
        $walletId = $request->getAttribute('walletId');
        $data = $request->getParsedBody();
        $amount = new Money($data['amount']);

        try {
            $transaction = $this->transactionService->withdraw($walletId, $amount);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'message' => 'Withdraw failed.',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Withdraw failed.',
                'errors' => $e->getMessage()
            ], 422);
        }

        return new JsonResponse([
            'message' => 'Withdraw completed successfully.',
            'data' => (new TransactionResource($transaction))->toArray()
        ], 201);
    }
}