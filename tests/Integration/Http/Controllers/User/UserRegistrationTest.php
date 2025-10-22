<?php

namespace Http\Controllers\User;

use App\Enums\UserType;
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
            'type' => UserType::COMMON->value
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
            'type' => 'type'
        ];

        $response = $this->postJson('/api/v1/users/register', $data);

        $this->assertEquals(422, $response->getStatusCode());

        unset($data['password'], $data['password_confirmation']);
        $this->assertDatabaseMissing('users', $data);
    }
}