<?php

namespace Tests\Unit\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Exceptions\ValidationException;
use App\Validators\TransactionValidator;
use PHPUnit\Framework\TestCase;

class TransactionValidatorTest extends TestCase
{
    private TransactionValidator $validator;
    private ExistsCheckerInterface $existsChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->existsChecker = $this->createMock(ExistsCheckerInterface::class);
        $this->validator = new TransactionValidator($this->existsChecker);
    }

    public function testValidateCreateReturnsValidatedDataOnSuccess(): void
    {
        $this->existsChecker->method('exists')->willReturn(true);

        $data = [
            'type' => 'DEPOSIT',
            'to_wallet_id' => 'some-wallet-id',
            'amount' => 100.50,
            'description' => 'Test Deposit',
            'status' => 'COMPLETED',
            'extra_field' => 'should_be_ignored',
        ];

        $validatedData = $this->validator->validateCreate($data);

        $this->assertArrayHasKey('type', $validatedData);
        $this->assertArrayHasKey('to_wallet_id', $validatedData);
        $this->assertArrayHasKey('amount', $validatedData);
        $this->assertArrayHasKey('description', $validatedData);
        $this->assertArrayHasKey('status', $validatedData);
        $this->assertArrayNotHasKey('extra_field', $validatedData);
        $this->assertEquals('DEPOSIT', $validatedData['type']);
    }

    public function testValidateCreateThrowsExceptionOnMissingRequiredField(): void
    {
        $data = [
            'to_wallet_id' => 'some-wallet-id',
            'amount' => 100.50,
        ];

        try {
            $this->validator->validateCreate($data);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('type', $errors);
            $this->assertEquals('The type is required.', $errors['type'][0]);
        }
    }
}
