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

    public function testSaveExecutesInsertStatementWithCorrectData(): void
    {
        $user = new User('John Doe', 'john@example.com', 'hashed_password', UserType::COMMON);

        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with("INSERT INTO users (name, email, password, type) VALUES (:name, :email, :password, :type)")
            ->willReturn($this->stmtMock);

        $this->stmtMock
            ->expects($this->exactly(4))
            ->method('bindValue');

        $this->stmtMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->repository->save($user);
    }

    public function testFindByEmailReturnUserDataWhenFound(): void
    {
        $email = 'john@example.com';
        $expectedUser = new User('John Doe', $email, 'hashed_password', UserType::COMMON);
        $expectedUser->setId(1);

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
            ->method('bindValue')
            ->with(':email', $email);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute');

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($userDataFromDb);

        $result = $this->repository->findByEmail($email);

        $this->assertEquals($expectedUser, $result);
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
            ->method('bindValue')
            ->with(':email', $email);

        $this->stmtMock
            ->expects($this->once())
            ->method('execute');

        $this->stmtMock
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }

    public function testFindByEmailUsesCorrectEmailParameter(): void
    {
        $email = 'test@example.com';

        $this->stmtMock
            ->expects($this->once())
            ->method('bindValue')
            ->with(':email', $email);

        $this->stmtMock
            ->method('execute');

        $this->stmtMock
            ->method('fetch')
            ->willReturn(false);

        $this->repository->findByEmail($email);
    }

}