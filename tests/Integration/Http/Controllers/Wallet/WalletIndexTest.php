<?php

namespace Http\Controllers\Wallet;

use App\Http\Controllers\WalletController;
use App\Services\WalletService;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class WalletIndexTest extends AppTestCase
{
    use DatabaseAssertions;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIndexSuccessfully(): void
    {
        $response = $this->get('/api/v1/wallets');

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $data);
    }

    public function testIndexFailsWhenServiceThrowsException(): void
    {
        $walletServiceMock = $this->createMock(WalletService::class);
        $walletServiceMock
            ->method('findAll')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $controller = new WalletController($walletServiceMock);

        $response = $controller->index();

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->__toString(), true);
        $this->assertEquals('Failed to retrieve wallets', $responseBody['error']);
        $this->assertEquals('Database connection failed', $responseBody['message']);
    }
}
