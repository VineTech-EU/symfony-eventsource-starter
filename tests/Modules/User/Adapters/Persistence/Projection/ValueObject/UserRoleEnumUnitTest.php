<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Persistence\Projection\ValueObject;

use App\Modules\User\Adapters\Persistence\Projection\ValueObject\UserRoleEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Adapters\Persistence\Projection\ValueObject\UserRoleEnum
 *
 * @internal
 */
final class UserRoleEnumUnitTest extends TestCase
{
    public function testIsAdminReturnsTrueForAdminRole(): void
    {
        // Arrange
        $role = UserRoleEnum::ROLE_ADMIN;

        // Act & Assert
        self::assertTrue($role->isAdmin());
        self::assertFalse($role->isUser());
    }

    public function testIsUserReturnsTrueForUserRole(): void
    {
        // Arrange
        $role = UserRoleEnum::ROLE_USER;

        // Act & Assert
        self::assertTrue($role->isUser());
        self::assertFalse($role->isAdmin());
    }

    public function testGetLabelReturnsCorrectHumanReadableText(): void
    {
        // Assert
        self::assertSame('User', UserRoleEnum::ROLE_USER->getLabel());
        self::assertSame('Administrator', UserRoleEnum::ROLE_ADMIN->getLabel());
    }

    public function testValuesReturnsAllRoleValues(): void
    {
        // Act
        $values = UserRoleEnum::values();

        // Assert
        self::assertCount(2, $values);
        self::assertContains('ROLE_USER', $values);
        self::assertContains('ROLE_ADMIN', $values);
    }
}
