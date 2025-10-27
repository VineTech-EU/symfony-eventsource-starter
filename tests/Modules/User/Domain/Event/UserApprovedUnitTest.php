<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\Event;

use App\Modules\User\Domain\Event\UserApproved;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserApproved domain event.
 * Tests event structure and immutability.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\Event\UserApproved
 */
final class UserApprovedUnitTest extends TestCase
{
    public function testUserApprovedEventContainsCorrectData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'user@example.com';
        $name = 'John Doe';

        // Act
        $event = new UserApproved($userId, $email, $name);

        // Assert
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());
    }

    public function testUserApprovedEventHasEventId(): void
    {
        // Act
        $event = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440000',
            'user@example.com',
            'User Name'
        );

        // Assert
        self::assertNotEmpty($event->getEventId());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $event->getEventId(),
            'Event ID should be a valid UUID v4'
        );
    }

    public function testUserApprovedEventHasEventName(): void
    {
        // Act
        $event = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440000',
            'user@example.com',
            'User Name'
        );

        // Assert
        self::assertSame('user.approved', UserApproved::getEventName()); // Static call
    }

    public function testUserApprovedEventHasOccurredOn(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $event = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440000',
            'user@example.com',
            'User Name'
        );

        $after = new \DateTimeImmutable();

        // Assert
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testDifferentEventsHaveDifferentEventIds(): void
    {
        // Act
        $event1 = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440001',
            'user1@example.com',
            'User 1'
        );

        $event2 = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440002',
            'user2@example.com',
            'User 2'
        );

        // Assert
        self::assertNotSame($event1->getEventId(), $event2->getEventId());
    }

    public function testUserApprovedEventIsImmutable(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'user@example.com';
        $name = 'User Name';

        // Act
        $event = new UserApproved($userId, $email, $name);

        // Assert - Verify getter returns same value (immutability)
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());

        // Second call should return same values
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());
    }

    public function testUserApprovedEventTracksCorrectUserId(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $event = new UserApproved($userId, 'user@example.com', 'User Name');

        // Assert
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $event->getUserId());
    }

    public function testMultipleApprovalEventsForDifferentUsers(): void
    {
        // Arrange
        $userId1 = '550e8400-e29b-41d4-a716-446655440001';
        $userId2 = '550e8400-e29b-41d4-a716-446655440002';

        // Act
        $event1 = new UserApproved($userId1, 'user1@example.com', 'User 1');
        $event2 = new UserApproved($userId2, 'user2@example.com', 'User 2');

        // Assert
        self::assertNotSame($event1->getUserId(), $event2->getUserId());
        self::assertNotSame($event1->getEventId(), $event2->getEventId());
    }

    public function testEventCanBeSerialized(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'user@example.com';
        $name = 'User Name';
        $event = new UserApproved($userId, $email, $name);

        // Act
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        self::assertInstanceOf(UserApproved::class, $unserialized);
        self::assertSame($event->getUserId(), $unserialized->getUserId());
        self::assertSame($event->getEmail(), $unserialized->getEmail());
        self::assertSame($event->getName(), $unserialized->getName());
        self::assertSame($event->getEventId(), $unserialized->getEventId());
    }

    public function testEventTimestampIsReasonable(): void
    {
        // Arrange
        $before = new \DateTimeImmutable('-1 second');
        $after = new \DateTimeImmutable('+1 second');

        // Act
        $event = new UserApproved(
            '550e8400-e29b-41d4-a716-446655440000',
            'user@example.com',
            'User Name'
        );

        // Assert
        self::assertGreaterThan($before, $event->getOccurredOn());
        self::assertLessThan($after, $event->getOccurredOn());
    }

    public function testUserApprovedEventToArrayIncludesAllData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'user@example.com';
        $name = 'User Name';

        $event = new UserApproved($userId, $email, $name);

        // Act
        $array = $event->toArray();

        // Assert
        self::assertArrayHasKey('user_id', $array);
        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('name', $array);
        self::assertArrayHasKey('event_id', $array);
        self::assertArrayHasKey('occurred_on', $array);

        self::assertSame($userId, $array['user_id']);
        self::assertSame($email, $array['email']);
        self::assertSame($name, $array['name']);
    }
}
