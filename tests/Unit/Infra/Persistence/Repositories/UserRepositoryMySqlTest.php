<?php

namespace Tests\Unit\Infra\Persistence\Repositories;

use App\Enums\UserType;
use App\Infra\Persistence\Repositories\UserRepositoryMySql;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class UserRepositoryMySqlTest extends TestCase
{
    private UserRepositoryMySql $repository;
    private PDO $pdoMock;
    private PDOStatement $stmtMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->stmtMock = $this->createMock(PDOStatement::class);

        $this->pdoMock
            ->method('prepare')
            ->willReturn($this->stmtMock);

        $this->repository = new UserRepositoryMySql($this->pdoMock);
    }

    public function testSaveCallsInsertWhenUserIdIsZero(): void
    {
        $user = User::create('John Doe', 'john@example.com', 'hashed_password', UserType::COMMON);
        $userWithId = $user->withId(1);

        $repository = $this->getMockBuilder(UserRepositoryMySql::class)
            ->setConstructorArgs([$this->pdoMock])
            ->onlyMethods(['insert', 'update'])
            ->getMock();

        $repository->expects($this->once())
            ->method('insert')
            ->with($this->identicalTo($user))
            ->willReturn($userWithId);

        $repository->expects($this->never())
            ->method('update');

        $result = $repository->save($user);

        $this->assertSame($userWithId, $result);
    }

    public function testSaveCallsInsertWhenUserIdIsNotZero(): void
    {
        $user = User::create('John Doe', 'john@example.com', 'hashed_password', UserType::COMMON);
        $user = $user->withId(1);

        $repository = $this->getMockBuilder(UserRepositoryMySql::class)
            ->setConstructorArgs([$this->pdoMock])
            ->onlyMethods(['insert', 'update'])
            ->getMock();

        $repository->expects($this->once())
            ->method('update')
            ->with($this->identicalTo($user))
            ->willReturn($user);

        $repository->expects($this->never())
            ->method('insert');

        $result = $repository->save($user);

        $this->assertSame($user, $result);
    }

    public function testFindByEmailReturnUserDataWhenFound(): void
    {
        $email = 'john@example.com';
        $expectedUser = User::create('John Doe', $email, 'hashed_password', UserType::COMMON);
        $expectedUser = $expectedUser->withId(1);

        $userDataFromDb = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => $email,
            'password' => 'hashed_password',
            'type' => 'COMMON'
        ];

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with("SELECT id, name, email, password, type FROM users WHERE email = :email")
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':email' => $email]);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($userDataFromDb);

        $result = $this->repository->findByEmail($email);

        $this->assertEquals($expectedUser, $result);
        $this->assertNotNull($result);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $email = 'nonexistent@example.com';

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with("SELECT id, name, email, password, type FROM users WHERE email = :email")
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->with([':email' => $email]);

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }

    public function testHydrateUser(): void
    {
        $userDataFromDb = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'hashed_password',
            'type' => 'COMMON'
        ];

        $expectedUser = User::reconstitute(
            $userDataFromDb['id'],
            $userDataFromDb['name'],
            $userDataFromDb['email'],
            $userDataFromDb['password'],
            UserType::from($userDataFromDb['type'])
        );

        $result = $this->repository->hydrate($userDataFromDb);

        $this->assertEquals($expectedUser, $result);
    }

}