<?php

namespace Tests\Unit\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Exceptions\ValidationException;
use App\Validators\WalletValidator;
use PHPUnit\Framework\TestCase;

class WalletValidatorTest extends TestCase
{

    private WalletValidator $validator;
    private ExistsCheckerInterface $existsChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->existsChecker = $this->createMock(ExistsCheckerInterface::class);
        $this->validator = new WalletValidator($this->existsChecker);
    }

    public function testValidateCreateReturnsValidatedDataOnSuccess(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        $validatedData = $this->validator->validateCreate([
            'user_id' => 1,
            'balance' => '100.00',
        ]);

        $this->assertArrayHasKey('user_id', $validatedData);
        $this->assertArrayHasKey('balance', $validatedData);
        $this->assertEquals(1, $validatedData['user_id']);
        $this->assertEquals('100.00', $validatedData['balance']);
    }

    public function testValidateCreateThrowsExceptionOnMissingRequiredField(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        try {
            $this->validator->validateCreate([]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('user_id', $errors);
            $this->assertArrayHasKey('balance', $errors);;
            $this->assertEquals('The user ID is required.', $errors['user_id'][0]);
            $this->assertEquals('The balance is required.', $errors['balance'][0]);
        }
    }

    public function validateCreateThrowsExceptionOnInexistentUserId(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        try {
            $this->validator->validateCreate([
                'user_id' => 1,
                'balance' => '100.00',
            ]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('user_id', $errors);
            $this->assertEquals('The user ID does not exist.', $errors['user_id'][0]);
        }
    }

    public function validateCreateThrowsExceptionOnInvalidBalance(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        try {
            $this->validator->validateCreate([
                'user_id' => 1,
                'balance' => 'invalid',
            ]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('balance', $errors);
            $this->assertEquals('The balance must be a number.', $errors['balance'][0]);
        }
    }

    public function validateCreateThrowsExceptionOnNegativeBalance(): void
    {
        $this->existsChecker
            ->method('exists')
            ->willReturn(false);

        try {
            $this->validator->validateCreate([
                'user_id' => 1,
                'balance' => '-100.00',
            ]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('balance', $errors);
            $this->assertEquals('The balance must be at least 0.', $errors['balance'][0]);
        }
    }
}