<?php

namespace Http\Controllers\Wallet;

use App\Enums\UserType;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class WalletShowTest extends AppTestCase
{
    use DatabaseAssertions;

    public function testShowSuccessfully(): void
    {
        $this->postJson('/api/v1/users/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'type' => UserType::COMMON->value
        ]);
        $responseGetWallets = $this->get('/api/v1/wallets');
        $walletId = json_decode($responseGetWallets->getBody()->getContents(), true)['data'][0]['id'];

        $response = $this->get("/api/v1/wallets/{$walletId}");

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertArrayHasKey('user_id', $data['data']);
        $this->assertArrayHasKey('balance', $data['data']);
        $this->assertArrayHasKey('balanceFormatted', $data['data']);
        $this->assertArrayHasKey('createdAt', $data['data']);
        $this->assertArrayHasKey('updatedAt', $data['data']);
        $this->assertEquals($walletId, $data['data']['id']);
    }

    public function testShowWalletNotFound(): void
    {
        $response = $this->get('/api/v1/wallets/1');
        $this->assertEquals(404, $response->getStatusCode());
    }
}