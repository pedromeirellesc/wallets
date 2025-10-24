<?php

namespace Http\Controllers\User;

use App\Enums\UserType;
use App\Http\Controllers\UserController;
use App\Services\UserService;
use Psr\Http\Message\ServerRequestInterface;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class UserLoginTest extends AppTestCase
{
    use DatabaseAssertions;

    private string $password = 'password';
    private array $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash($this->password, PASSWORD_DEFAULT),
            'type' => UserType::COMMON->value,
        ];

        $sql = "INSERT INTO users (name, email, password, type) VALUES (:name, :email, :password, :type)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($this->userData);
    }

    public function testLoginSuccessfully(): void
    {
        $data = [
            'email' => $this->userData['email'],
            'password' => $this->password,
        ];

        $response = $this->postJson('/api/v1/users/login', $data);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $response = $this->postJson('/api/v1/users/login', [
            'email' => $this->userData['email'],
            'password' => 'wrong-password',
        ]);

        $this->assertEquals(401, $response->getStatusCode());

        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid credentials.', $responseData['error']);
    }

    public function testLoginFailsWhenServiceThrowsException(): void
    {
        $userServiceMock = $this->createMock(UserService::class);
        $userServiceMock
            ->method('login')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $controller = new UserController($userServiceMock);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->method('getParsedBody')
            ->willReturn([
                'email' => $this->userData['email'],
                'password' => $this->password,
            ]);

        $response = $controller->login($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('error', $responseBody);
    }
}
