<?php

namespace App\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Exceptions\ValidationException;
use App\Utils\Arr;

class WalletValidator extends Validator
{
    public function __construct(
        private readonly ExistsCheckerInterface $existsChecker,
    ) {}

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'balance' => ['required', 'numeric'],
        ];
    }

    protected function messages(): array
    {
        return [
            'user_id.required' => 'The user ID is required.',
            'user_id.exists' => 'The user ID does not exist.',
            'balance.required' => 'The balance is required.',
            'balance.numeric' => 'The balance must be a number.',
            'balance.min' => 'The balance must be at least 0.',
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
