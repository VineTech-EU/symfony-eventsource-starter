<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\Http\Validation;

use App\SharedKernel\Adapters\Http\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @covers \App\SharedKernel\Adapters\Http\Validation\ValidationException
 *
 * @internal
 */
final class ValidationExceptionUnitTest extends TestCase
{
    public function testConstructorWithConstraintViolationList(): void
    {
        // Arrange
        $violation = new ConstraintViolation(
            'Email is required',
            null,
            [],
            null,
            'email',
            null
        );
        $violations = new ConstraintViolationList([$violation]);

        // Act
        $exception = new ValidationException($violations);

        // Assert
        self::assertSame('Validation failed', $exception->getMessage());
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithCustomMessage(): void
    {
        // Arrange
        $violations = new ConstraintViolationList();

        // Act
        $exception = new ValidationException($violations, 'Custom validation error');

        // Assert
        self::assertSame('Custom validation error', $exception->getMessage());
    }

    public function testGetErrorsWithConstraintViolationList(): void
    {
        // Arrange
        $violation1 = new ConstraintViolation(
            'Email is required',
            null,
            [],
            null,
            'email',
            null
        );
        $violation2 = new ConstraintViolation(
            'Name must not be blank',
            null,
            [],
            null,
            'name',
            null
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $exception = new ValidationException($violations);

        // Act
        $errors = $exception->getErrors();

        // Assert
        self::assertCount(2, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertSame(['Email is required'], $errors['email']);
        self::assertSame(['Name must not be blank'], $errors['name']);
    }

    public function testGetErrorsWithArray(): void
    {
        // Arrange
        $violations = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password is too short'],
        ];
        $exception = new ValidationException($violations);

        // Act
        $errors = $exception->getErrors();

        // Assert
        self::assertSame($violations, $errors);
    }

    public function testGetErrorsGroupsMultipleViolationsForSameField(): void
    {
        // Arrange
        $violation1 = new ConstraintViolation(
            'Email is required',
            null,
            [],
            null,
            'email',
            null
        );
        $violation2 = new ConstraintViolation(
            'Email must be valid',
            null,
            [],
            null,
            'email',
            null
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $exception = new ValidationException($violations);

        // Act
        $errors = $exception->getErrors();

        // Assert
        self::assertCount(1, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertCount(2, $errors['email']);
        self::assertSame('Email is required', $errors['email'][0]);
        self::assertSame('Email must be valid', $errors['email'][1]);
    }

    public function testGetFirstErrorReturnsFirstViolationMessage(): void
    {
        // Arrange
        $violation1 = new ConstraintViolation(
            'Email is required',
            null,
            [],
            null,
            'email',
            null
        );
        $violation2 = new ConstraintViolation(
            'Name must not be blank',
            null,
            [],
            null,
            'name',
            null
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);
        $exception = new ValidationException($violations);

        // Act
        $firstError = $exception->getFirstError();

        // Assert
        self::assertSame('Email is required', $firstError);
    }

    public function testGetFirstErrorWithArrayViolations(): void
    {
        // Arrange
        $violations = [
            'password' => ['Password is too short'],
            'email' => ['Email is required'],
        ];
        $exception = new ValidationException($violations);

        // Act
        $firstError = $exception->getFirstError();

        // Assert
        self::assertSame('Password is too short', $firstError);
    }

    public function testGetFirstErrorReturnsDefaultMessageWhenNoViolations(): void
    {
        // Arrange
        $violations = [];
        $exception = new ValidationException($violations, 'Custom error message');

        // Act
        $firstError = $exception->getFirstError();

        // Assert
        self::assertSame('Custom error message', $firstError);
    }

    public function testGetFirstErrorWithEmptyConstraintViolationList(): void
    {
        // Arrange
        $violations = new ConstraintViolationList();
        $exception = new ValidationException($violations, 'No violations found');

        // Act
        $firstError = $exception->getFirstError();

        // Assert
        self::assertSame('No violations found', $firstError);
    }
}
