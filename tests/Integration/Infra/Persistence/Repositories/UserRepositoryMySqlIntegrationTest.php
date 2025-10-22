<?php

namespace Tests\Integration\Infra\Persistence\Repositories;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\UserRepositoryMySql;
use App\Models\User;
use Tests\AppTestCase;
use Tests\Traits\DatabaseAssertions;

class UserRepositoryMySqlIntegrationTest extends AppTestCase
{
    use DatabaseAssertions;

    private UserRepositoryMySql $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepositoryMySql(self::getConnection());
    }

    public function testSaveInsertUserSuccessfully(): void
    {
        $user = new User('John Doe', 'john@example.com', 'hashed_password_123', UserType::COMMON);

        $this->repository->save($user);

        $userData = [
            'name' => $user->name(),
            'email' => $user->email(),
            'password' => $user->password(),
            'type' => $user->type(),
        ];
        $this->assertDatabaseHas('users', $userData);
    }

    public function testSaveMultipleUsersWithDifferentEmails(): void
    {
        $users = [
            new User(
                'John Doe',
                'john@example.com',
                'hash1',
                UserType::COMMON
            ),
            new User(
                'Jane Smith',
                'jane@example.com',
                'hash2',
                UserType::COMMON
            )
        ];

        foreach ($users as $userData) {
            $this->repository->save($userData);
        }

        foreach ($users as $userData) {
            $userData = [
                'name' => $userData->name(),
                'email' => $userData->email(),
                'password' => $userData->password(),
                'type' => $userData->type(),
            ];
            $this->assertDatabaseHas('users', $userData);
        }
    }

    public function testFindByEmailReturnsUserData(): void
    {
        $userData = new User(
            'John Doe',
            'john@example.com',
            'hashed_password',
            UserType::COMMON
        );

        $this->repository->save($userData);

        $result = $this->repository->findByEmail('john@example.com');

        $this->assertIsObject($result);
        $this->assertEquals('John Doe', $result->name());
        $this->assertEquals('john@example.com', $result->email());
        $this->assertEquals('hashed_password', $result->password());
        $this->assertEquals('COMMON', $result->type());
    }

    public function testFindByEmailReturnsNullWhenUserNotExists(): void
    {
        $result = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    public function testFindByEmailFindsByExactEmail(): void
    {
        $userData = new User(
            'John Doe',
            'john@example.com',
            'hash',
            UserType::COMMON
        );

        $this->repository->save($userData);

        $result = $this->repository->findByEmail('john@example');
        $this->assertNull($result);

        $result = $this->repository->findByEmail('john@example.com');
        $this->assertNotNull($result);
    }

    public function testFindByEmailReturnCompleteUserData(): void
    {
        $userData = new User(
            'Jane Smith',
            'jane@example.com',
            'secure_hash',
            UserType::COMMON
        );

        $this->repository->save($userData);

        $result = $this->repository->findByEmail('jane@example.com');

        $this->assertObjectHasProperty('id', $result);
        $this->assertObjectHasProperty('name', $result);
        $this->assertObjectHasProperty('email', $result);
        $this->assertObjectHasProperty('password', $result);
        $this->assertObjectHasProperty('type', $result);

        $this->assertIsInt($result->id());
        $this->assertIsString($result->name());
        $this->assertIsString($result->email());
        $this->assertIsString($result->password());
        $this->assertIsString($result->type());
    }

    public function testSavePreservesDataIntegrity(): void
    {
        $originalData = new User(
            'Complex Name With Spëcial Çhars',
            'test+tag@example.co.uk',
            'p@$$w0rd!#%',
            UserType::COMMON
        );

        $this->repository->save($originalData);

        $result = $this->repository->findByEmail($originalData->email());

        $this->assertEquals($originalData->name(), $result->name());
        $this->assertEquals($originalData->email(), $result->email());
        $this->assertEquals($originalData->password(), $result->password());
        $this->assertEquals($originalData->type(), $result->type());
    }

    public function testFindByEmailAfterMultipleSaves(): void
    {
        $user1 = new User(
            'User One',
            'user1@example.com',
            'hash1',
            UserType::COMMON
        );

        $user2 = new User(
            'User Two',
            'user2@example.com',
            'hash2',
            UserType::COMMON
        );

        $this->repository->save($user1);
        $this->repository->save($user2);

        $result1 = $this->repository->findByEmail('user1@example.com');
        $result2 = $this->repository->findByEmail('user2@example.com');

        $this->assertEquals('User One', $result1->name());
        $this->assertEquals('User Two', $result2->name());
        $this->assertNotEquals($result1->id(), $result2->id());
        $this->assertEquals('COMMON', $result1->type());
        $this->assertEquals('COMMON', $result2->type());
    }
}