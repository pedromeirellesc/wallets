<?php

namespace Tests\Integration;

use Tests\AppTestCase;

class AppStatusGetTest extends AppTestCase
{
    public function testAppStatusGet(): void
    {
        $response = $this->get('/status');
        $this->assertEquals(200, $response->getStatusCode());
    }
}
