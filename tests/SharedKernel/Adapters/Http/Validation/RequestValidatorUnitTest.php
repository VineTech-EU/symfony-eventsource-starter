<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\Http\Validation;

use App\SharedKernel\Adapters\Http\Validation\RequestValidator;
use App\SharedKernel\Adapters\Http\Validation\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \App\SharedKernel\Adapters\Http\Validation\RequestValidator
 *
 * @internal
 */
final class RequestValidatorUnitTest extends TestCase
{
    private MockObject&ValidatorInterface $validator;
    private RequestValidator $requestValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->requestValidator = new RequestValidator($this->validator);
    }

    public function testValidatePassesWithNoViolations(): void
    {
        // Arrange
        $request = new \stdClass();
        $violations = new ConstraintViolationList();

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Act - should not throw exception
        $this->requestValidator->validate($request);

        // Assert - if we reach here, validation passed
    }

    public function testValidateThrowsExceptionWithViolations(): void
    {
        // Arrange
        $request = new \stdClass();
        $violation = new ConstraintViolation(
            'Email is required',
            null,
            [],
            null,
            'email',
            null
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->requestValidator->validate($request);
    }

    public function testGetErrorsReturnsEmptyArrayForValidRequest(): void
    {
        // Arrange
        $request = new \stdClass();
        $violations = new ConstraintViolationList();

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Act
        $errors = $this->requestValidator->getErrors($request);

        // Assert
        self::assertSame([], $errors);
    }

    public function testGetErrorsReturnsFormattedViolations(): void
    {
        // Arrange
        $request = new \stdClass();
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

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Act
        $errors = $this->requestValidator->getErrors($request);

        // Assert
        self::assertCount(2, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertSame(['Email is required'], $errors['email']);
        self::assertSame(['Name must not be blank'], $errors['name']);
    }

    public function testGetErrorsGroupsMultipleViolationsForSameField(): void
    {
        // Arrange
        $request = new \stdClass();
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

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Act
        $errors = $this->requestValidator->getErrors($request);

        // Assert
        self::assertCount(1, $errors);
        self::assertArrayHasKey('email', $errors);
        self::assertCount(2, $errors['email']);
        self::assertSame('Email is required', $errors['email'][0]);
        self::assertSame('Email must be valid', $errors['email'][1]);
    }

    public function testValidateWithComplexRequest(): void
    {
        // Arrange
        $request = new class {
            public string $email = 'invalid-email';
            public string $password = '123'; // Too short
        };

        $violation1 = new ConstraintViolation(
            'This value is not a valid email address.',
            null,
            [],
            null,
            'email',
            'invalid-email'
        );
        $violation2 = new ConstraintViolation(
            'Password must be at least 8 characters long.',
            null,
            [],
            null,
            'password',
            '123'
        );
        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($request)
            ->willReturn($violations)
        ;

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->requestValidator->validate($request);
    }
}
