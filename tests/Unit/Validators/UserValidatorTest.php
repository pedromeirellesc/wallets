<?php

namespace Tests\Unit\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Exceptions\ValidationException;
use App\Validators\UserValidator;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;
    private ExistsCheckerInterface $existsChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->existsChecker = $this->createMock(ExistsCheckerInterface::class);
        $this->validator = new UserValidator($this->existsChecker);
    }

    public function testValidateCreateReturnsValidatedDataOnSuccess(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
            'extra_field' => 'should_be_ignored',
        ];

        $validatedData = $this->validator->validateCreate($data);

        $this->assertArrayHasKey('name', $validatedData);
        $this->assertArrayHasKey('email', $validatedData);
        $this->assertArrayHasKey('password', $validatedData);
        $this->assertArrayHasKey('password_confirmation', $validatedData);
        $this->assertArrayNotHasKey('extra_field', $validatedData);
        $this->assertEquals('John Doe', $validatedData['name']);
        $this->assertEquals('john@example.com', $validatedData['email']);
    }

    public function testValidateCreateThrowsExceptionOnMissingRequiredField(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertEquals('The name field is required.', $errors['name'][0]);
        }
    }

    public function testValidateCreateThrowsExceptionOnInvalidEmail(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertEquals('The email must be a valid email address.', $errors['email'][0]);
        }
    }

    public function testValidateCreateThrowsExceptionWhenEmailAlreadyExists(): void
    {
        $email = 'john@example.com';

        $this->existsChecker
            ->expects($this->once())
            ->method('exists')
            ->with('users', 'email', $email)
            ->willReturn(true);

        $data = [
            'name' => 'John Doe',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertEquals('This email is already in use.', $errors['email'][0]);
        }
    }

    public function testValidateCreateThrowsExceptionOnPasswordTooShort(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('password', $errors);
            $this->assertEquals('The password must be at least 8 characters long.', $errors['password'][0]);
        }
    }

    public function testValidateCreateThrowsExceptionWhenPasswordConfirmationDoesNotMatch(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password1234',
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('password', $errors);
            $this->assertEquals('The password confirmation does not match.', $errors['password'][0]);
        }
    }

    public function testValidateCreateThrowsExceptionOnMultipleErrors(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'different',
            'type' => 'type',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
        }
    }

    public function testValidateCreateThrowsExceptionOnPasswordTooLong(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => str_repeat('a', 256),
            'password_confirmation' => str_repeat('a', 256),
            'type' => 'COMMON',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('password', $errors);
        }
    }

    public function testValidateCreateThrowsExceptionOnMissingType(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('type', $errors);
        }
    }

    public function testValidateCreateThrowsExceptionOnInvalidType(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'INVALID',
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('type', $errors);
        }
    }
}
