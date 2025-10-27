<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\UseCase;

use App\Modules\User\Application\Command\DTO\ChangeUserEmailRequest;
use App\Modules\User\Application\UseCase\ChangeUserEmail;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Modules\User\Domain\Exception\UserNotFoundException;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ChangeUserEmail use case.
 * Tests Event Sourcing reconstruction and email change logic.
 *
 * @internal
 *
 * @covers \App\Modules\User\Application\UseCase\ChangeUserEmail
 */
final class ChangeUserEmailUnitTest extends TestCase
{
    public function testChangeEmailUpdatesUserEmail(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new ChangeUserEmailRequest(
            userId: $userId,
            newEmail: 'newemail@example.com'
        );

        // Create a user with old email
        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('old@example.com'),
            'John Doe'
        );
        $user->pullDomainEvents(); // Clear creation event

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('get')
            ->with(self::callback(static fn (UserId $id): bool => $id->toString() === $userId))
            ->willReturn($user)
        ;

        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return $user->getEmail()->toString() === 'newemail@example.com';
            }))
        ;

        $useCase = new ChangeUserEmail($repository);

        // Act
        $useCase->execute($request);

        // Assert
        self::assertSame('newemail@example.com', $user->getEmail()->toString());
    }

    public function testChangeEmailRecordsUserEmailChangedEvent(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new ChangeUserEmailRequest(
            userId: $userId,
            newEmail: 'new@example.com'
        );

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('old@example.com'),
            'John Doe'
        );
        $user->pullDomainEvents();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('get')->willReturn($user);
        $repository->method('save');

        $useCase = new ChangeUserEmail($repository);

        // Act
        $useCase->execute($request);

        // Assert - Should have recorded UserEmailChanged event
        $events = $user->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserEmailChanged::class, $events[0]);
        self::assertSame('user.email_changed', UserEmailChanged::getEventName()); // Static call
    }

    public function testChangeEmailWithNonExistentUserThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new ChangeUserEmailRequest(
            userId: $userId,
            newEmail: 'new@example.com'
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('get')
            ->willThrowException(new UserNotFoundException($userId))
        ;

        $repository->expects(self::never())->method('save');

        $useCase = new ChangeUserEmail($repository);

        // Act & Assert
        $this->expectException(UserNotFoundException::class);

        $useCase->execute($request);
    }

    public function testChangeEmailToSameEmailThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $currentEmail = 'same@example.com';
        $request = new ChangeUserEmailRequest(
            userId: $userId,
            newEmail: $currentEmail  // Same as current
        );

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString($currentEmail),
            'John Doe'
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('get')->willReturn($user);
        $repository->expects(self::never())->method('save');

        $useCase = new ChangeUserEmail($repository);

        // Act & Assert
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/same email/i');

        $useCase->execute($request);
    }

    public function testChangeEmailWithInvalidEmailThrowsException(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $request = new ChangeUserEmailRequest(
            userId: $userId,
            newEmail: 'invalid-email-format'
        );

        $user = User::create(
            UserId::fromString($userId),
            Email::fromString('old@example.com'),
            'John Doe'
        );

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('get')->willReturn($user);
        $repository->expects(self::never())->method('save');

        $useCase = new ChangeUserEmail($repository);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $useCase->execute($request);
    }
}
