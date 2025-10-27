<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Http\Validation;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Request Validator.
 *
 * Validates Request DTOs and formats validation errors.
 */
final readonly class RequestValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {}

    /**
     * Validate a request DTO.
     *
     * @throws ValidationException if validation fails
     */
    public function validate(object $request): void
    {
        $violations = $this->validator->validate($request);

        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }
    }

    /**
     * Get validation errors as array.
     *
     * @return array<string, list<string>>
     */
    public function getErrors(object $request): array
    {
        $violations = $this->validator->validate($request);

        return $this->formatViolations($violations);
    }

    /**
     * Format violations as array.
     *
     * @return array<string, list<string>>
     */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath][] = (string) $violation->getMessage();
        }

        return $errors;
    }
}
