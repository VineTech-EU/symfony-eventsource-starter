<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\UseCase;

use App\Modules\User\Application\UseCase\ApproveUser;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Exception\UserNotFoundException;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApproveUser use case.
 * Tests user approval business logic.
 *
 * @internal
 *
 * @covers \App\Modules\User\Application\UseCase\ApproveUser
 */
final class ApproveUserUnitTest extends TestCase
{
    public function testApproveUserChangesStatusToApproved(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('john@example.com'),
            'John Doe'
        );
        $user->pullDomainEvents(); // Clear creation event

        self::assertTrue($user->isPending());
        self::assertFalse($user->isApproved());

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('get')
            ->willReturn($user)
        ;

        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return $user->isApproved() && !$user->isPending();
            }))
        ;

        $useCase = new ApproveUser($repository);

        // Act
        $useCase->execute($userId);

        // Assert
        self::assertTrue($user->isApproved());
        self::assertFalse($user->isPending());
    }

    public function testApproveUserRecordsUserApprovedEvent(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('test@example.com'),
            'Test User'
        );
        $user->pullDomainEvents();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('get')->willReturn($user);
        $repository->method('save');

        $useCase = new ApproveUser($repository);

        // Act
        $useCase->execute($userId);

        // Assert - Should have recorded UserApproved event
        $events = $user->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserApproved::class, $events[0]);
        self::assertSame('user.approved', UserApproved::getEventName()); // Static call
    }

    public function testApproveNonExistentUserThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('get')
            ->willThrowException(new UserNotFoundException($userId))
        ;

        $repository->expects(self::never())->method('save');

        $useCase = new ApproveUser($repository);

        // Act & Assert
        $this->expectException(UserNotFoundException::class);

        $useCase->execute($userId);
    }

    public function testApproveAlreadyApprovedUserThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('john@example.com'),
            'John Doe'
        );
        $user->approve(); // Already approved

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('get')->willReturn($user);
        $repository->expects(self::never())->method('save');

        $useCase = new ApproveUser($repository);

        // Act & Assert
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/not pending/i');

        $useCase->execute($userId);
    }
}
