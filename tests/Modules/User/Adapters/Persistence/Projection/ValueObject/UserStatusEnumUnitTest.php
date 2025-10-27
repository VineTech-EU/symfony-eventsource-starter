<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Persistence\Projection\ValueObject;

use App\Modules\User\Adapters\Persistence\Projection\ValueObject\UserStatusEnum;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Adapters\Persistence\Projection\ValueObject\UserStatusEnum
 *
 * @internal
 */
final class UserStatusEnumUnitTest extends TestCase
{
    public function testIsPendingReturnsTrueForPendingStatus(): void
    {
        // Arrange
        $status = UserStatusEnum::PENDING;

        // Act & Assert
        self::assertTrue($status->isPending());
        self::assertFalse($status->isApproved());
        self::assertFalse($status->isRejected());
    }

    public function testIsApprovedReturnsTrueForApprovedStatus(): void
    {
        // Arrange
        $status = UserStatusEnum::APPROVED;

        // Act & Assert
        self::assertTrue($status->isApproved());
        self::assertFalse($status->isPending());
        self::assertFalse($status->isRejected());
    }

    public function testIsRejectedReturnsTrueForRejectedStatus(): void
    {
        // Arrange
        $status = UserStatusEnum::REJECTED;

        // Act & Assert
        self::assertTrue($status->isRejected());
        self::assertFalse($status->isPending());
        self::assertFalse($status->isApproved());
    }

    public function testGetLabelReturnsCorrectHumanReadableText(): void
    {
        // Assert
        self::assertSame('Pending Approval', UserStatusEnum::PENDING->getLabel());
        self::assertSame('Approved', UserStatusEnum::APPROVED->getLabel());
        self::assertSame('Rejected', UserStatusEnum::REJECTED->getLabel());
    }

    public function testGetColorReturnsCorrectColorForEachStatus(): void
    {
        // Assert
        self::assertSame('orange', UserStatusEnum::PENDING->getColor());
        self::assertSame('green', UserStatusEnum::APPROVED->getColor());
        self::assertSame('red', UserStatusEnum::REJECTED->getColor());
    }

    public function testValuesReturnsAllStatusValues(): void
    {
        // Act
        $values = UserStatusEnum::values();

        // Assert
        self::assertCount(3, $values);
        self::assertContains('pending', $values);
        self::assertContains('approved', $values);
        self::assertContains('rejected', $values);
    }
}
