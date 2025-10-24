<?php

namespace App\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Utils\Arr;

class UserValidator extends Validator
{
    public function __construct(protected ExistsCheckerInterface $existsChecker)
    {
    }

    public function validateCreate(array $data): array
    {
        $allowedKeys = array_keys($this->rules());
        $allowedKeys[] = 'password_confirmation';
        $filteredData = Arr::only($data, $allowedKeys);

        $this->validate($filteredData);

        return $filteredData;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'password' => ['required', 'min:8', 'max:255', 'confirmed'],
            'type' => ['required', 'in:COMMON,SHOPKEEPER'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email is already in use.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.max' => 'The password must be at most 255 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'type.required' => 'The user type is required.',
            'type.in' => 'The user type must be either COMMON or SHOPKEEPER.',
        ];
    }

    protected function validateConfirmed(mixed $value, mixed $confirmation): bool
    {
        return $value === $confirmation;
    }

    protected function setExistsChecker(ExistsCheckerInterface $checker): void
    {
        $this->existsChecker = $checker;
    }

    protected function getExistsChecker(): ExistsCheckerInterface
    {
        return $this->existsChecker;
    }
}
