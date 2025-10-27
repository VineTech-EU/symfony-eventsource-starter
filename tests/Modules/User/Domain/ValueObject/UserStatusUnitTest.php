<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\ValueObject;

use App\Modules\User\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserStatus value object.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\ValueObject\UserStatus
 */
final class UserStatusUnitTest extends TestCase
{
    public function testPendingCreatesPendingStatus(): void
    {
        // Act
        $status = UserStatus::pending();

        // Assert
        self::assertSame('pending', $status->toString());
        self::assertTrue($status->isPending());
        self::assertFalse($status->isApproved());
        self::assertFalse($status->isRejected());
    }

    public function testApprovedCreatesApprovedStatus(): void
    {
        // Act
        $status = UserStatus::approved();

        // Assert
        self::assertSame('approved', $status->toString());
        self::assertTrue($status->isApproved());
        self::assertFalse($status->isPending());
        self::assertFalse($status->isRejected());
    }

    public function testRejectedCreatesRejectedStatus(): void
    {
        // Act
        $status = UserStatus::rejected();

        // Assert
        self::assertSame('rejected', $status->toString());
        self::assertTrue($status->isRejected());
        self::assertFalse($status->isPending());
        self::assertFalse($status->isApproved());
    }

    public function testFromStringWithValidStatusCreatesStatus(): void
    {
        // Act
        $pending = UserStatus::fromString('pending');
        $approved = UserStatus::fromString('approved');
        $rejected = UserStatus::fromString('rejected');

        // Assert
        self::assertTrue($pending->isPending());
        self::assertTrue($approved->isApproved());
        self::assertTrue($rejected->isRejected());
    }

    public function testFromStringWithInvalidStatusThrowsException(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user status');

        UserStatus::fromString('invalid');
    }

    public function testEqualsReturnsTrueForSameStatus(): void
    {
        // Arrange
        $status1 = UserStatus::pending();
        $status2 = UserStatus::pending();

        // Act & Assert
        self::assertTrue($status1->equals($status2));
    }

    public function testEqualsReturnsFalseForDifferentStatuses(): void
    {
        // Arrange
        $pending = UserStatus::pending();
        $approved = UserStatus::approved();

        // Act & Assert
        self::assertFalse($pending->equals($approved));
    }
}
