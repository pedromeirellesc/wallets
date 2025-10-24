<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Infra\Persistence\Repositories\Contracts\UserRepositoryContract;
use App\Models\User;
use App\Services\Auth\JwtTokenGenerator;
use App\Services\Auth\PasswordHasher;
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
    private PasswordHasher $passwordHasherMock;
    private JwtTokenGenerator $tokenGeneratorMock;
    private array $validUserData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepositoryMock = $this->createMock(UserRepositoryContract::class);
        $this->walletServiceMock = $this->createMock(WalletService::class);
        $this->userValidatorMock = $this->createMock(UserValidator::class);
        $this->passwordHasherMock = $this->createMock(PasswordHasher::class);
        $this->tokenGeneratorMock = $this->createMock(JwtTokenGenerator::class);

        $this->userService = new UserService(
            $this->userRepositoryMock,
            $this->walletServiceMock,
            $this->userValidatorMock,
            $this->passwordHasherMock,
            $this->tokenGeneratorMock,
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
        $inputData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
        ];

        $validatedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'type' => 'COMMON',
        ];

        $hashedPassword = 'hashed_password_xyz';
        $savedUser = User::create('John Doe', 'john@example.com', $hashedPassword, UserType::COMMON)
            ->withId(42);

        $this->userValidatorMock
            ->expects($this->once())
            ->method('validateCreate')
            ->with($inputData)
            ->willReturn($validatedData);

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('hash')
            ->with('password123')
            ->willReturn($hashedPassword);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($hashedPassword) {
                return $user->name() === 'John Doe'
                    && $user->email() === 'john@example.com'
                    && $user->password() === $hashedPassword
                    && $user->type() === UserType::COMMON;
            }))
            ->willReturn($savedUser);

        $this->walletServiceMock
            ->expects($this->once())
            ->method('createWalletForUser')
            ->with(42);

        $result = $this->userService->register($inputData);

        $this->assertSame($savedUser, $result);
        $this->assertEquals(42, $result->id());
    }

    public function testRegisterThrowsExceptionWhenValidationFails(): void
    {
        $inputData = [
            'name' => 'John',
            'email' => 'invalid-email',
        ];

        $this->userValidatorMock
            ->expects($this->once())
            ->method('validateCreate')
            ->willThrowException(new ValidationException(['email' => ['Invalid email']]));

        $this->passwordHasherMock
            ->expects($this->never())
            ->method('hash');

        $this->userRepositoryMock
            ->expects($this->never())
            ->method('save');

        $this->walletServiceMock
            ->expects($this->never())
            ->method('createWalletForUser');

        $this->expectException(ValidationException::class);

        $this->userService->register($inputData);
    }

    public function testRegisterHashesPassword(): void
    {
        $this->userValidatorMock
            ->method('validateCreate')
            ->willReturn([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'plain_password',
                'type' => 'COMMON',
            ]);

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('hash')
            ->with('plain_password')
            ->willReturn('super_secure_hash');

        $this->userRepositoryMock
            ->method('save')
            ->willReturn(User::create('John', 'john@example.com', 'super_secure_hash', UserType::COMMON)->withId(1));

        $this->walletServiceMock
            ->method('createWalletForUser');

        $this->userService->register([]);
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

    public function testLoginSuccessfully(): void
    {
        $email = 'john@example.com';
        $password = 'password123';
        $hashedPassword = 'hashed_password_xyz';
        $expectedToken = 'jwt.token.here';

        $user = User::create('John Doe', $email, $hashedPassword, UserType::COMMON)
            ->withId(1);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(true);

        $this->tokenGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with($this->identicalTo($user))
            ->willReturn($expectedToken);

        $result = $this->userService->login($email, $password);

        $this->assertEquals($expectedToken, $result);
    }

    public function testLoginThrowsExceptionWhenUserNotFound(): void
    {
        $email = 'notfound@example.com';
        $password = 'password123';

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordHasherMock
            ->expects($this->never())
            ->method('verify');

        $this->tokenGeneratorMock
            ->expects($this->never())
            ->method('generate');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->userService->login($email, $password);
    }

    public function testLoginThrowsExceptionWhenPasswordIsIncorrect(): void
    {
        $email = 'john@example.com';
        $password = 'wrong_password';
        $hashedPassword = 'hashed_password_xyz';

        $user = User::create('John Doe', $email, $hashedPassword, UserType::COMMON)
            ->withId(1);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(false);

        $this->tokenGeneratorMock
            ->expects($this->never())
            ->method('generate');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->userService->login($email, $password);
    }

    public function testLoginVerifiesPasswordWithCorrectArguments(): void
    {
        $email = 'test@example.com';
        $plainPassword = 'my_plain_password';
        $hashedPassword = '$2y$10$hashedvalue';

        $user = User::create('Test User', $email, $hashedPassword, UserType::COMMON)
            ->withId(5);

        $this->userRepositoryMock
            ->method('findByEmail')
            ->willReturn($user);

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('verify')
            ->with(
                $this->equalTo($plainPassword),
                $this->equalTo($hashedPassword),
            )
            ->willReturn(true);

        $this->tokenGeneratorMock
            ->method('generate')
            ->willReturn('token');

        $this->userService->login($email, $plainPassword);
    }

    public function testLoginGeneratesTokenWithCorrectUser(): void
    {
        $email = 'alice@example.com';
        $password = 'password';
        $hashedPassword = 'hashed';

        $user = User::create('Alice', $email, $hashedPassword, UserType::SHOPKEEPER)
            ->withId(42);

        $this->userRepositoryMock
            ->method('findByEmail')
            ->willReturn($user);

        $this->passwordHasherMock
            ->method('verify')
            ->willReturn(true);

        $this->tokenGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(function (User $u) use ($user) {
                return $u === $user
                    && $u->id() === 42
                    && $u->email() === 'alice@example.com'
                    && $u->type() === UserType::SHOPKEEPER;
            }))
            ->willReturn('generated.token.abc');

        $result = $this->userService->login($email, $password);

        $this->assertEquals('generated.token.abc', $result);
    }

    public function testLoginDoesNotGenerateTokenWhenPasswordIsWrong(): void
    {
        $email = 'user@example.com';
        $password = 'wrong';

        $user = User::create('User', $email, 'hashed', UserType::COMMON)
            ->withId(1);

        $this->userRepositoryMock
            ->method('findByEmail')
            ->willReturn($user);

        $this->passwordHasherMock
            ->method('verify')
            ->willReturn(false);

        $this->tokenGeneratorMock
            ->expects($this->never())
            ->method('generate');

        try {
            $this->userService->login($email, $password);
            $this->fail('Should have thrown AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertEquals('Invalid credentials.', $e->getMessage());
        }
    }

    public function testLoginReturnsStringToken(): void
    {
        $user = User::create('User', 'user@example.com', 'hashed', UserType::COMMON)
            ->withId(1);

        $this->userRepositoryMock
            ->method('findByEmail')
            ->willReturn($user);

        $this->passwordHasherMock
            ->method('verify')
            ->willReturn(true);

        $this->tokenGeneratorMock
            ->method('generate')
            ->willReturn('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.example');

        $result = $this->userService->login('user@example.com', 'password');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
