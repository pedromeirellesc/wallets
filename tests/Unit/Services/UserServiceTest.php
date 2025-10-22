<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Exceptions\ValidationException;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use App\Services\UserService;
use App\Services\WalletService;
use App\Validators\UserValidator;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{

    private UserRepositoryContract $userRepositoryMock;
    private UserValidator $userValidatorMock;
    private WalletService $walletServiceMock;
    private UserService $userService;
    private array $validUserData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepositoryMock = $this->createMock(UserRepositoryContract::class);
        $this->walletServiceMock = $this->createMock(WalletService::class);
        $this->userValidatorMock = $this->createMock(UserValidator::class);

        $this->userService = new UserService(
            $this->userRepositoryMock,
            $this->walletServiceMock,
            $this->userValidatorMock
        );

        $this->validUserData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
        ];
    }

    public function testRegisterSuccessfully(): void
    {
        $this->userValidatorMock
            ->expects($this->once())
            ->method('validateCreate');

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save');

        $this->walletServiceMock
            ->expects($this->once())
            ->method('createWalletForUser');

        $this->userService->register($this->validUserData);
    }

    public function testRegisterHashesPasswordBeforeSaving(): void
    {
        $originalPassword = $this->validUserData['password'];

        $user = new User($this->validUserData['name'], $this->validUserData['email'], $this->validUserData['password'], UserType::from($this->validUserData['type']));
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function ($user) use ($originalPassword) {
                    $this->assertNotEquals($originalPassword, $user->password());
                    $this->assertTrue(password_verify($originalPassword, $user->password()));
                    return true;
                })
            );

        $this->userService->register($this->validUserData);
    }

    public function testRegisterThrowsExceptionAndDoesNotSaveWhenValidationFails(): void
    {
        $this->expectException(ValidationException::class);

        $this->userValidatorMock
            ->expects($this->once())
            ->method('validateCreate')
            ->willThrowException(new ValidationException(['email' => 'Email already exists.']));

        $this->userRepositoryMock
            ->expects($this->never())
            ->method('save');

        $this->userService->register($this->validUserData);
    }
}