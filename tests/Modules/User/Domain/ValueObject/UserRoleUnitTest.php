<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\ValueObject;

use App\Modules\User\Domain\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserRole value object.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\ValueObject\UserRole
 */
final class UserRoleUnitTest extends TestCase
{
    public function testUserCreatesUserRole(): void
    {
        // Act
        $role = UserRole::user();

        // Assert
        self::assertSame('ROLE_USER', $role->toString());
        self::assertTrue($role->isUser());
        self::assertFalse($role->isAdmin());
    }

    public function testAdminCreatesAdminRole(): void
    {
        // Act
        $role = UserRole::admin();

        // Assert
        self::assertSame('ROLE_ADMIN', $role->toString());
        self::assertTrue($role->isAdmin());
        self::assertFalse($role->isUser());
    }

    public function testFromStringWithValidRoleCreatesRole(): void
    {
        // Act
        $userRole = UserRole::fromString('ROLE_USER');
        $adminRole = UserRole::fromString('ROLE_ADMIN');

        // Assert
        self::assertTrue($userRole->isUser());
        self::assertTrue($adminRole->isAdmin());
    }

    public function testFromStringWithInvalidRoleThrowsException(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user role');

        UserRole::fromString('ROLE_INVALID');
    }

    public function testEqualsReturnsTrueForSameRole(): void
    {
        // Arrange
        $role1 = UserRole::user();
        $role2 = UserRole::user();

        // Act & Assert
        self::assertTrue($role1->equals($role2));
    }

    public function testEqualsReturnsFalseForDifferentRoles(): void
    {
        // Arrange
        $userRole = UserRole::user();
        $adminRole = UserRole::admin();

        // Act & Assert
        self::assertFalse($userRole->equals($adminRole));
    }
}
