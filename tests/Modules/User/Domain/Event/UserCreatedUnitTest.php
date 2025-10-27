<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\Event;

use App\Modules\User\Domain\Event\UserCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserCreated domain event.
 * Tests event structure and immutability.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\Event\UserCreated
 */
final class UserCreatedUnitTest extends TestCase
{
    public function testUserCreatedEventContainsCorrectData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'john@example.com';
        $name = 'John Doe';
        $roles = ['ROLE_USER'];
        $status = 'pending';

        // Act
        $event = new UserCreated(
            userId: $userId,
            email: $email,
            name: $name,
            roles: $roles,
            status: $status
        );

        // Assert
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());
        self::assertSame($roles, $event->getRoles());
        self::assertSame($status, $event->getStatus());
    }

    public function testUserCreatedEventHasEventId(): void
    {
        // Act
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'test@example.com',
            name: 'Test User',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Assert
        self::assertNotEmpty($event->getEventId());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $event->getEventId(),
            'Event ID should be a valid UUID v4'
        );
    }

    public function testUserCreatedEventHasEventName(): void
    {
        // Act
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'test@example.com',
            name: 'Test User',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Assert
        self::assertSame('user.created', UserCreated::getEventName()); // Static call
    }

    public function testUserCreatedEventHasOccurredOn(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'test@example.com',
            name: 'Test User',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        $after = new \DateTimeImmutable();

        // Assert
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testUserCreatedEventWithMultipleRoles(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440001';
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];

        // Act
        $event = new UserCreated(
            userId: $userId,
            email: 'admin@example.com',
            name: 'Admin User',
            roles: $roles,
            status: 'approved'
        );

        // Assert
        self::assertCount(2, $event->getRoles());
        self::assertSame($roles, $event->getRoles());
    }

    public function testUserCreatedEventWithApprovedStatus(): void
    {
        // Arrange
        $status = 'approved';

        // Act
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'approved@example.com',
            name: 'Approved User',
            roles: ['ROLE_USER'],
            status: $status
        );

        // Assert
        self::assertSame($status, $event->getStatus());
    }

    public function testUserCreatedEventWithRejectedStatus(): void
    {
        // Arrange
        $status = 'rejected';

        // Act
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'rejected@example.com',
            name: 'Rejected User',
            roles: ['ROLE_USER'],
            status: $status
        );

        // Assert
        self::assertSame($status, $event->getStatus());
    }

    public function testDifferentEventsHaveDifferentEventIds(): void
    {
        // Act
        $event1 = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440001',
            email: 'user1@example.com',
            name: 'User 1',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        $event2 = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440002',
            email: 'user2@example.com',
            name: 'User 2',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Assert
        self::assertNotSame($event1->getEventId(), $event2->getEventId());
    }

    public function testUserCreatedEventIsImmutable(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'immutable@example.com';
        $name = 'Immutable User';
        $roles = ['ROLE_USER'];
        $status = 'pending';

        // Act
        $event = new UserCreated(
            userId: $userId,
            email: $email,
            name: $name,
            roles: $roles,
            status: $status
        );

        // Assert - Verify all getters return same values (immutability)
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());
        self::assertSame($roles, $event->getRoles());
        self::assertSame($status, $event->getStatus());

        // Second call should return same values
        self::assertSame($userId, $event->getUserId());
        self::assertSame($email, $event->getEmail());
        self::assertSame($name, $event->getName());
        self::assertSame($roles, $event->getRoles());
        self::assertSame($status, $event->getStatus());
    }

    public function testUserCreatedEventToArrayIncludesAllData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'test@example.com';
        $name = 'Test User';
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $status = 'approved';

        $event = new UserCreated(
            userId: $userId,
            email: $email,
            name: $name,
            roles: $roles,
            status: $status
        );

        // Act
        $array = $event->toArray();

        // Assert
        self::assertArrayHasKey('user_id', $array);
        self::assertArrayHasKey('email', $array);
        self::assertArrayHasKey('name', $array);
        self::assertArrayHasKey('roles', $array);
        self::assertArrayHasKey('status', $array);
        self::assertArrayHasKey('event_id', $array);
        self::assertArrayHasKey('occurred_on', $array);

        self::assertSame($userId, $array['user_id']);
        self::assertSame($email, $array['email']);
        self::assertSame($name, $array['name']);
        self::assertSame($roles, $array['roles']);
        self::assertSame($status, $array['status']);
    }
}
