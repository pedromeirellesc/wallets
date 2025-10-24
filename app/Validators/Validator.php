<?php

namespace App\Validators;

use App\Contracts\ExistsCheckerInterface;
use App\Exceptions\ValidationException;

abstract class Validator
{
    protected array $errors = [];

    abstract protected function rules(): array;
    abstract protected function messages(): array;
    abstract protected function getExistsChecker(): ExistsCheckerInterface;

    public function validate(array $data): array
    {
        $this->errors = [];
        $rules = $this->rules();
        $validatedData = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $validatedData[$field] = $value;
            $this->applyRules($field, $value, $fieldRules, $data);
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $validatedData;
    }

    private function applyRules(string $field, mixed $value, array $rules, array $data): void
    {
        foreach ($rules as $rule) {
            $params = [];
            if (str_contains($rule, ':')) {
                [$rule, $paramString] = explode(':', $rule, 2);
                $params = explode(',', $paramString);
            }

            $method = 'validate' . ucfirst($rule);

            if (!method_exists($this, $method)) {
                continue;
            }

            $isValid = match ($rule) {
                'confirmed' => $this->$method($value, $data[$field . '_confirmation'] ?? null),
                default => $this->$method($value, ...$params),
            };

            if (!$isValid) {
                $this->addError($field, $rule);
            }
        }
    }

    private function addError(string $field, string $rule): void
    {
        $messageKey = "{$field}.{$rule}";
        $ruleName = strtok($rule, ':');
        $messageKey = "{$field}.{$ruleName}";

        $message = $this->messages()[$messageKey] ?? "The {$field} field is invalid.";
        $this->errors[$field][] = $message;
    }

    protected function validateRequired(mixed $value): bool
    {
        return is_numeric($value) ? !is_null($value) : !empty($value);
    }

    protected function validateUnique(mixed $value, string $table, string $column): bool
    {
        return !$this->getExistsChecker()->exists($table, $column, $value);
    }

    protected function validateExists(mixed $value, string $table, string $column): bool
    {
        return $this->getExistsChecker()->exists($table, $column, $value);
    }

    protected function validateNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    protected function validateEmail(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(mixed $value, string $length): bool
    {
        return is_string($value) && mb_strlen($value) >= (int)$length;
    }

    protected function validateMax(mixed $value, string $length): bool
    {
        return is_string($value) && mb_strlen($value) <= (int)$length;
    }

    protected function validateIn(mixed $value, ...$allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }
}
