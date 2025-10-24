<?php

namespace Http\Controllers\Wallet;

use App\Enums\UserType;
use App\Http\Controllers\WalletController;
use App\Services\WalletService;
use Psr\Http\Message\ServerRequestInterface;
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
            'type' => UserType::COMMON->value,
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
        $this->assertArrayHasKey('amount', $data['data']['balance']);
        $this->assertArrayHasKey('formatted', $data['data']['balance']);
        $this->assertArrayHasKey('cents', $data['data']['balance']);
        $this->assertArrayHasKey('created_at', $data['data']);
        $this->assertArrayHasKey('updated_at', $data['data']);
        $this->assertEquals($walletId, $data['data']['id']);
    }

    public function testShowWalletNotFound(): void
    {
        $response = $this->get('/api/v1/wallets/1');
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testShowFailsWhenServiceThrowsException(): void
    {
        $walletServiceMock = $this->createMock(WalletService::class);
        $walletServiceMock
            ->method('findById')
            ->willThrowException(new \RuntimeException('Database query failed'));

        $controller = new WalletController($walletServiceMock);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getAttribute')
            ->with('uuid')
            ->willReturn('some-uuid-123');

        $response = $controller->show($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->__toString(), true);
        $this->assertEquals('Failed to retrieve wallet', $responseBody['error']);
        $this->assertEquals('Database query failed', $responseBody['message']);
    }
}
