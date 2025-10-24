<?php

namespace Tests\Traits;

use App\Enums\UserType;

trait CreatesUsers
{
    protected function createUser(
        string $name = 'Test User',
        string $email = 'test@example.com',
        string $password = 'password',
        UserType $type = UserType::COMMON,
    ): array {
        $response = $this->postJson('/api/v1/users/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'type' => $type->value,
        ]);

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['user_id'])) {
            $data['data'] = ['id' => $data['user_id']];
        }

        return $data;
    }

    protected function createUserWithWallet(
        string $name = 'Test User',
        string $email = 'test@example.com',
        UserType $type = UserType::COMMON,
    ): string {
        $userData = $this->createUser($name, $email, 'password', $type);

        if (!isset($userData['data']['id'])) {
            throw new \RuntimeException(
                'Failed to create user: no user ID returned. Response: ' .
                json_encode($userData, JSON_PRETTY_PRINT),
            );
        }

        $userId = $userData['data']['id'];

        try {
            return $this->getWalletIdByUserId($userId);
        } catch (\RuntimeException $e) {
            $walletsResponse = $this->get('/api/v1/wallets');
            $walletsData = json_decode($walletsResponse->getBody()->getContents(), true);

            throw new \RuntimeException(
                $e->getMessage() . "\n" .
                "User ID: {$userId}\n" .
                "All wallets: " . json_encode($walletsData, JSON_PRETTY_PRINT),
            );
        }
    }

    protected function getFirstWalletId(): string
    {
        $response = $this->get('/api/v1/wallets');
        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['data'])) {
            throw new \RuntimeException('No wallets found');
        }

        return $data['data'][0]['id'];
    }

    protected function getWalletIdByUserId(int $userId): string
    {
        $response = $this->get('/api/v1/wallets');

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Failed to fetch wallets: HTTP {$response->getStatusCode()}");
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \RuntimeException("Invalid response format from wallets endpoint");
        }

        foreach ($data['data'] as $wallet) {
            if (isset($wallet['user_id']) && $wallet['user_id'] === $userId) {
                return $wallet['id'];
            }
        }

        throw new \RuntimeException("No wallet found for user ID: {$userId}");
    }

    protected function getAllWalletIds(): array
    {
        $response = $this->get('/api/v1/wallets');
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn ($wallet) => $wallet['id'], $data['data']);
    }
}
