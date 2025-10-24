<?php

namespace App\Exceptions;

class ValidationException extends \InvalidArgumentException
{
    private array $errors;

    public function __construct(array $errors, string $message = "The given data was invalid.", int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
