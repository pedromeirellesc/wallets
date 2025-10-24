<?php

namespace Http\Controllers\User;

use App\Enums\UserType;
use App\Http\Controllers\UserController;
use App\Services\UserService;
use Psr\Http\Message\ServerRequestInterface;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class UserRegistrationTest extends AppTestCase
{
    use DatabaseAssertions;

    public function testRegisterSuccessfully(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'type' => UserType::COMMON->value,
        ];

        $response = $this->postJson('/api/v1/users/register', $data);

        $this->assertEquals(201, $response->getStatusCode());

        unset($data['password'], $data['password_confirmation']);
        $this->assertDatabaseHas('users', $data);
        $this->assertDatabaseHas('wallets', ['user_id' => 1]);
    }

    public function testRegisterWithInvalidFields(): void
    {
        $data = [
            'name' => '123',
            'email' => 'john@example',
            'password' => '123',
            'password_confirmation' => '123',
            'type' => 'type',
        ];

        $response = $this->postJson('/api/v1/users/register', $data);

        $this->assertEquals(422, $response->getStatusCode());

        unset($data['password'], $data['password_confirmation']);
        $this->assertDatabaseMissing('users', $data);
    }

    public function testRegisterFailsWhenServiceThrowsException(): void
    {
        $userServiceMock = $this->createMock(UserService::class);
        $userServiceMock
            ->method('register')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $controller = new UserController($userServiceMock);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getParsedBody')
            ->willReturn([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'type' => UserType::COMMON->value,
            ]);

        $response = $controller->register($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->__toString(), true);
        $this->assertEquals('Registration failed.', $responseBody['error']);
        $this->assertEquals('Database connection failed', $responseBody['message']);
    }
}
