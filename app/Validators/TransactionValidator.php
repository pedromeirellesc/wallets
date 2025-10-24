<?php

namespace App\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Utils\Arr;

class TransactionValidator extends Validator
{
    public function __construct(
        private readonly ExistsCheckerInterface $existsChecker,
    ) {
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:DEPOSIT,WITHDRAW,TRANSFER                                                                                                                                  '],
            'from_wallet_id' => ['exists:wallets,id'],
            'to_wallet_id' => ['exists:wallets,id'],
            'amount' => ['required', 'numeric'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:PENDING,COMPLETED,FAILED'],
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required' => 'The type is required.',
            'type.in' => 'The type must be deposit, withdraw, or transfer.',
            'from_wallet_id.exists' => 'The from wallet ID does not exist.',
            'to_wallet_id.exists' => 'The to wallet ID does not exist.',
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a number.',
            'description.required' => 'The description is required.',
            'description.string' => 'The description must be a string.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be pending, completed, or failed.',
        ];
    }

    public function validateCreate(array $data): array
    {
        $allowedKeys = array_keys($this->rules());
        $filteredData = Arr::only($data, $allowedKeys);

        $this->validate($filteredData);

        return $filteredData;
    }

    protected function getExistsChecker(): ExistsCheckerInterface
    {
        return $this->existsChecker;
    }
}
