<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\Entity;

use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User aggregate root.
 * Tests business logic and invariants without any infrastructure dependencies.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\Entity\User
 */
final class UserUnitTest extends TestCase
{
    public function testCreateUserRecordsUserCreatedEvent(): void
    {
        // Arrange
        $id = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $email = Email::fromString('john@example.com');
        $name = 'John Doe';

        // Act
        $user = User::create($id, $email, $name);

        // Assert
        self::assertSame($id->toString(), $user->getId());
        self::assertSame($email->toString(), $user->getEmail()->toString());
        self::assertSame($name, $user->getName());
        self::assertTrue($user->isPending());
        self::assertFalse($user->isApproved());

        // Check that event was recorded
        $events = $user->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);

        /** @var UserCreated $event */
        $event = $events[0];
        self::assertSame($id->toString(), $event->getUserId());
        self::assertSame($email->toString(), $event->getEmail());
        self::assertSame($name, $event->getName());
    }

    public function testApproveUserRecordsUserApprovedEvent(): void
    {
        // Arrange
        $user = User::create(
            UserId::fromString(SymfonyUuid::generate()->toString()),
            Email::fromString('john@example.com'),
            'John Doe',
        );
        $user->pullDomainEvents(); // Clear creation event

        // Act
        $user->approve();

        // Assert
        self::assertTrue($user->isApproved());
        self::assertFalse($user->isPending());

        $events = $user->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserApproved::class, $events[0]);
    }

    public function testApproveAlreadyApprovedUserThrowsException(): void
    {
        // Arrange
        $user = User::create(
            UserId::fromString(SymfonyUuid::generate()->toString()),
            Email::fromString('john@example.com'),
            'John Doe',
        );
        $user->approve();

        // Act & Assert
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot approve user .+: user is not pending/');

        $user->approve();
    }

    public function testChangeEmailRecordsUserEmailChangedEvent(): void
    {
        // Arrange
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        $user = User::create(
            UserId::fromString(SymfonyUuid::generate()->toString()),
            Email::fromString($oldEmail),
            'John Doe',
        );
        $user->pullDomainEvents(); // Clear creation event

        // Act
        $user->changeEmail(Email::fromString($newEmail));

        // Assert
        self::assertSame($newEmail, $user->getEmail()->toString());

        $events = $user->getDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserEmailChanged::class, $events[0]);

        /** @var UserEmailChanged $event */
        $event = $events[0];
        self::assertSame($oldEmail, $event->getOldEmail());
        self::assertSame($newEmail, $event->getNewEmail());
    }

    public function testChangeEmailToSameEmailThrowsException(): void
    {
        // Arrange
        $email = 'john@example.com';
        $user = User::create(
            UserId::fromString(SymfonyUuid::generate()->toString()),
            Email::fromString($email),
            'John Doe',
        );

        // Act & Assert - Should throw exception
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot change to the same email address');

        $user->changeEmail(Email::fromString($email));
    }
}
