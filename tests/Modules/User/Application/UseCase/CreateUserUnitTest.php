<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\UseCase;

use App\Modules\User\Application\Command\DTO\CreateUserRequest;
use App\Modules\User\Application\UseCase\CreateUser;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateUser use case.
 * Tests business logic without infrastructure dependencies.
 *
 * @internal
 *
 * @covers \App\Modules\User\Application\UseCase\CreateUser
 */
final class CreateUserUnitTest extends TestCase
{
    public function testCreateUserSavesUserToRepository(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new CreateUserRequest(
            email: 'john@example.com',
            name: 'John Doe'
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user) use ($userId): bool {
                return $user->getId() === $userId
                    && $user->getEmail()->toString() === 'john@example.com'
                    && $user->getName() === 'John Doe'
                    && $user->isPending();
            }))
        ;

        $useCase = new CreateUser($repository);

        // Act
        $useCase->execute($userId, $request);

        // Assert - Verified by mock expectations
    }

    public function testCreateUserRecordsUserCreatedEvent(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new CreateUserRequest(
            email: 'test@example.com',
            name: 'Test User'
        );

        $capturedUser = null;
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (User $user) use (&$capturedUser): void {
                $capturedUser = $user;
            })
        ;

        $useCase = new CreateUser($repository);

        // Act
        $useCase->execute($userId, $request);

        // Assert - User should have recorded UserCreated event
        self::assertNotNull($capturedUser);
        $events = $capturedUser->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);
        self::assertSame('user.created', UserCreated::getEventName()); // Static call
    }

    public function testCreateUserWithInvalidEmailThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new CreateUserRequest(
            email: 'invalid-email',  // Invalid format
            name: 'John Doe'
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::never())->method('save');

        $useCase = new CreateUser($repository);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/email/i');

        $useCase->execute($userId, $request);
    }
}
