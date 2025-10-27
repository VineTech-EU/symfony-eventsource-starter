<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\EventStore;

use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\SharedKernel\Adapters\EventStore\DomainEventNormalizer;
use App\SharedKernel\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DomainEventNormalizer.
 *
 * Tests edge cases and error handling for the custom normalizer:
 * - Invalid data types
 * - Invalid event types
 * - Missing required parameters
 * - Snake_case to camelCase mapping
 * - Default parameter values
 * - supportsDenormalization logic
 * - getSupportedTypes
 *
 * @internal
 *
 * @covers \App\SharedKernel\Adapters\EventStore\DomainEventNormalizer
 */
final class DomainEventNormalizerUnitTest extends TestCase
{
    private DomainEventNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new DomainEventNormalizer();
    }

    public function testDenormalizeWithValidSnakeCaseData(): void
    {
        // Arrange
        $data = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
        ];

        // Act
        $event = $this->normalizer->denormalize($data, UserCreated::class);

        // Assert
        self::assertInstanceOf(UserCreated::class, $event);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $event->getUserId());
        self::assertSame('john@example.com', $event->getEmail());
        self::assertSame('John Doe', $event->getName());
    }

    public function testDenormalizeWithCamelCaseData(): void
    {
        // Arrange - Using camelCase instead of snake_case
        $data = [
            'userId' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'roles' => ['ROLE_ADMIN'],
            'status' => 'approved',
        ];

        // Act
        $event = $this->normalizer->denormalize($data, UserCreated::class);

        // Assert
        self::assertInstanceOf(UserCreated::class, $event);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $event->getUserId());
        self::assertSame('jane@example.com', $event->getEmail());
    }

    public function testDenormalizeWithNonArrayDataThrowsException(): void
    {
        // Arrange
        $data = 'not-an-array';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an array for DomainEvent deserialization');

        // Act
        $this->normalizer->denormalize($data, UserCreated::class);
    }

    public function testDenormalizeWithNonDomainEventTypeThrowsException(): void
    {
        // Arrange
        $data = ['foo' => 'bar'];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "stdClass" must be a DomainEvent class');

        // Act
        $this->normalizer->denormalize($data, \stdClass::class);
    }

    public function testDenormalizeWithMissingRequiredParameterThrowsException(): void
    {
        // Arrange - Missing 'name' parameter
        $data = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'test@example.com',
            // 'name' is missing
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
        ];

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter "name" for event');

        // Act
        $this->normalizer->denormalize($data, UserCreated::class);
    }

    public function testSupportsDenormalizationReturnsTrueForDomainEvent(): void
    {
        // Act
        $supports = $this->normalizer->supportsDenormalization([], UserCreated::class);

        // Assert
        self::assertTrue($supports);
    }

    public function testSupportsDenormalizationReturnsFalseForNonDomainEvent(): void
    {
        // Act
        $supports = $this->normalizer->supportsDenormalization([], \stdClass::class);

        // Assert
        self::assertFalse($supports);
    }

    public function testGetSupportedTypesReturnsDomainEventClass(): void
    {
        // Act
        $types = $this->normalizer->getSupportedTypes(null);

        // Assert
        self::assertArrayHasKey(DomainEvent::class, $types);
        self::assertTrue($types[DomainEvent::class]);
    }

    public function testDenormalizeHandlesComplexSnakeCaseMapping(): void
    {
        // Arrange - Test complex snake_case like 'old_email' → 'oldEmail'
        $data = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'old_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ];

        // Act
        $event = $this->normalizer->denormalize(
            $data,
            UserEmailChanged::class
        );

        // Assert
        self::assertInstanceOf(UserEmailChanged::class, $event);
        self::assertSame('old@example.com', $event->getOldEmail());
        self::assertSame('new@example.com', $event->getNewEmail());
    }
}
