<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Http\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Validation Exception.
 *
 * Thrown when request validation fails.
 * Contains structured validation errors.
 */
final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, list<string>>|ConstraintViolationListInterface $violations
     */
    public function __construct(
        private readonly array|ConstraintViolationListInterface $violations,
        string $message = 'Validation failed',
    ) {
        parent::__construct($message);
    }

    /**
     * Get formatted errors.
     *
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        if (\is_array($this->violations)) {
            return $this->violations;
        }

        /** @var array<string, list<string>> $errors */
        $errors = [];

        foreach ($this->violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = (string) $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Get first error message.
     */
    public function getFirstError(): string
    {
        $errors = $this->getErrors();

        if ([] === $errors) {
            return $this->getMessage();
        }

        $firstField = array_key_first($errors);

        return $errors[$firstField][0] ?? $this->getMessage();
    }
}
