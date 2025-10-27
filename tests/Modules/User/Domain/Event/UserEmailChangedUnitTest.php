<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\Event;

use App\Modules\User\Domain\Event\UserEmailChanged;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserEmailChanged domain event.
 * Tests event structure and immutability.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\Event\UserEmailChanged
 */
final class UserEmailChangedUnitTest extends TestCase
{
    public function testUserEmailChangedEventContainsCorrectData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        // Act
        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Assert
        self::assertSame($userId, $event->getUserId());
        self::assertSame($oldEmail, $event->getOldEmail());
        self::assertSame($newEmail, $event->getNewEmail());
    }

    public function testUserEmailChangedEventHasEventId(): void
    {
        // Act
        $event = new UserEmailChanged(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com'
        );

        // Assert
        self::assertNotEmpty($event->getEventId());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $event->getEventId(),
            'Event ID should be a valid UUID v4'
        );
    }

    public function testUserEmailChangedEventHasEventName(): void
    {
        // Act
        $event = new UserEmailChanged(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com'
        );

        // Assert
        self::assertSame('user.email_changed', UserEmailChanged::getEventName()); // Static call
    }

    public function testUserEmailChangedEventHasOccurredOn(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $event = new UserEmailChanged(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com'
        );

        $after = new \DateTimeImmutable();

        // Assert
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testDifferentEventsHaveDifferentEventIds(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $event1 = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'old1@example.com',
            newEmail: 'new1@example.com'
        );

        $event2 = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'old2@example.com',
            newEmail: 'new2@example.com'
        );

        // Assert
        self::assertNotSame($event1->getEventId(), $event2->getEventId());
    }

    public function testUserEmailChangedEventIsImmutable(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        // Act
        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Assert - Verify all getters return same values (immutability)
        self::assertSame($userId, $event->getUserId());
        self::assertSame($oldEmail, $event->getOldEmail());
        self::assertSame($newEmail, $event->getNewEmail());

        // Second call should return same values
        self::assertSame($userId, $event->getUserId());
        self::assertSame($oldEmail, $event->getOldEmail());
        self::assertSame($newEmail, $event->getNewEmail());
    }

    public function testOldAndNewEmailAreDifferent(): void
    {
        // Arrange
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        // Act
        $event = new UserEmailChanged(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Assert
        self::assertNotSame($oldEmail, $newEmail);
        self::assertNotSame($event->getOldEmail(), $event->getNewEmail());
    }

    public function testEventTracksEmailChangeAccurately(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $oldEmail = 'john.doe@example.com';
        $newEmail = 'john.newemail@example.com';

        // Act
        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Assert - Verify exact email addresses
        self::assertSame('john.doe@example.com', $event->getOldEmail());
        self::assertSame('john.newemail@example.com', $event->getNewEmail());
        self::assertSame($userId, $event->getUserId());
    }

    public function testEventCanTrackMultipleEmailChangesForSameUser(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        // Act - Simulate multiple email changes
        $change1 = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'email1@example.com',
            newEmail: 'email2@example.com'
        );

        $change2 = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'email2@example.com',
            newEmail: 'email3@example.com'
        );

        // Assert - Each event is independent
        self::assertSame($userId, $change1->getUserId());
        self::assertSame($userId, $change2->getUserId());
        self::assertNotSame($change1->getEventId(), $change2->getEventId());

        // Verify event chain
        self::assertSame($change1->getNewEmail(), $change2->getOldEmail());
    }

    public function testUserEmailChangedEventToArrayIncludesAllData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Act
        $array = $event->toArray();

        // Assert
        self::assertArrayHasKey('user_id', $array);
        self::assertArrayHasKey('old_email', $array);
        self::assertArrayHasKey('new_email', $array);
        self::assertArrayHasKey('event_id', $array);
        self::assertArrayHasKey('occurred_on', $array);

        self::assertSame($userId, $array['user_id']);
        self::assertSame($oldEmail, $array['old_email']);
        self::assertSame($newEmail, $array['new_email']);
    }
}
