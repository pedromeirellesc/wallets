<?php

namespace Http\Controllers\Wallet;

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
}
