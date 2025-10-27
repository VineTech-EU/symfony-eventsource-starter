<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\ValueObject;

use App\Modules\User\Domain\ValueObject\UserId;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserId value object.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\ValueObject\UserId
 */
final class UserIdUnitTest extends TestCase
{
    public function testFromStringCreatesValidUserId(): void
    {
        // Arrange
        $uuid = SymfonyUuid::generate()->toString();

        // Act
        $userId = UserId::fromString($uuid);

        // Assert
        $uuidString = $userId->toString();
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuidString,
            'UUID should be in valid format'
        );
    }

    public function testFromStringCreatesUniqueIds(): void
    {
        // Act
        $userId1 = UserId::fromString(SymfonyUuid::generate()->toString());
        $userId2 = UserId::fromString(SymfonyUuid::generate()->toString());

        // Assert
        self::assertNotSame($userId1->toString(), $userId2->toString());
    }

    public function testFromStringCreatesUserIdFromValidUuid(): void
    {
        // Arrange
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $userId = UserId::fromString($uuidString);

        // Assert
        self::assertSame($uuidString, $userId->toString());
    }

    public function testEqualsReturnsTrueForSameId(): void
    {
        // Arrange
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $userId1 = UserId::fromString($uuidString);
        $userId2 = UserId::fromString($uuidString);

        // Act & Assert
        self::assertTrue($userId1->equals($userId2));
    }

    public function testEqualsReturnsFalseForDifferentIds(): void
    {
        // Arrange
        $userId1 = UserId::fromString(SymfonyUuid::generate()->toString());
        $userId2 = UserId::fromString(SymfonyUuid::generate()->toString());

        // Act & Assert
        self::assertFalse($userId1->equals($userId2));
    }
}
