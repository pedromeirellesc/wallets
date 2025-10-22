<?php

namespace Tests\Traits;

use App\Enums\UserType;

trait CreatesUsers
{
    protected function createUser(
        string $name = 'Test User',
        string $email = 'test@example.com',
        string $password = 'password',
        UserType $type = UserType::COMMON
    ): array {
        $response = $this->postJson('/api/v1/users/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'type' => $type->value
        ]);

        $this->assertEquals(201, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function createUserWithWallet(
        string $name = 'Test User',
        string $email = 'test@example.com',
        UserType $type = UserType::COMMON
    ): string {
        $this->createUser($name, $email, 'password', $type);

        return $this->getFirstWalletId();
    }

    protected function getFirstWalletId(): string
    {
        $response = $this->get('/api/v1/wallets');
        $data = json_decode($response->getBody()->getContents(), true);

        return $data['data'][0]['id'];
    }

    protected function getAllWalletIds(): array
    {
        $response = $this->get('/api/v1/wallets');
        $data = json_decode($response->getBody()->getContents(), true);

        return array_map(fn($wallet) => $wallet['id'], $data['data']);
    }
}