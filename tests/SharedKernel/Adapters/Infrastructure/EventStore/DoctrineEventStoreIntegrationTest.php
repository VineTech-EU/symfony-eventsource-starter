<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\EventStore;

use App\Modules\User\Domain\Event\UserCreated;
use App\SharedKernel\Domain\EventStore\EventStoreException;
use App\SharedKernel\Domain\EventStore\EventStoreInterface;
use App\Tests\Support\IntegrationTestCase;

/**
 * Integration tests for DoctrineEventStore.
 * Tests Event Store with real database: append, retrieve, concurrency control.
 *
 * @internal
 *
 * @covers \App\SharedKernel\Adapters\EventStore\DoctrineEventStore
 */
final class DoctrineEventStoreIntegrationTest extends IntegrationTestCase
{
    private EventStoreInterface $eventStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStore = self::getContainer()->get(EventStoreInterface::class);
    }

    public function testAppendStoresEventsInDatabase(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440000';
        $event = new UserCreated(
            userId: $aggregateId,
            email: 'john@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Act
        $this->eventStore->append($aggregateId, [$event], expectedVersion: 0);

        // Assert
        $events = $this->eventStore->getEventsForAggregate($aggregateId);
        self::assertCount(1, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);
    }

    public function testAppendMultipleEventsIncrementsVersion(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440001';
        $event1 = new UserCreated(
            userId: $aggregateId,
            email: 'user1@example.com',
            name: 'User 1',
            roles: ['ROLE_USER'],
            status: 'pending'
        );
        $event2 = new UserCreated(
            userId: $aggregateId,
            email: 'user2@example.com',
            name: 'User 2',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Act
        $this->eventStore->append($aggregateId, [$event1], expectedVersion: 0);
        $this->eventStore->append($aggregateId, [$event2], expectedVersion: 1);

        // Assert
        $events = $this->eventStore->getEventsForAggregate($aggregateId);
        self::assertCount(2, $events);
    }

    public function testAppendWithWrongExpectedVersionThrowsConcurrencyException(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440002';
        $event = new UserCreated(
            userId: $aggregateId,
            email: 'test@example.com',
            name: 'Test User',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Assert
        $this->expectException(EventStoreException::class);
        $this->expectExceptionMessage('Concurrency conflict');

        // Act - Try to append with wrong expected version
        $this->eventStore->append($aggregateId, [$event], expectedVersion: 5);
    }

    public function testGetEventsForAggregateReturnsEventsInOrder(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440003';
        $event1 = new UserCreated(
            userId: $aggregateId,
            email: 'first@example.com',
            name: 'First Event',
            roles: ['ROLE_USER'],
            status: 'pending'
        );
        $event2 = new UserCreated(
            userId: $aggregateId,
            email: 'second@example.com',
            name: 'Second Event',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        $this->eventStore->append($aggregateId, [$event1], expectedVersion: 0);
        $this->eventStore->append($aggregateId, [$event2], expectedVersion: 1);

        // Act
        $events = $this->eventStore->getEventsForAggregate($aggregateId);

        // Assert
        self::assertCount(2, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);
        self::assertInstanceOf(UserCreated::class, $events[1]);
        self::assertSame('First Event', $events[0]->getName());
        self::assertSame('Second Event', $events[1]->getName());
    }

    public function testGetEventsForAggregateFromVersionReturnsOnlyNewerEvents(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440004';
        $event1 = new UserCreated(
            userId: $aggregateId,
            email: 'event1@example.com',
            name: 'Event 1',
            roles: ['ROLE_USER'],
            status: 'pending'
        );
        $event2 = new UserCreated(
            userId: $aggregateId,
            email: 'event2@example.com',
            name: 'Event 2',
            roles: ['ROLE_USER'],
            status: 'pending'
        );
        $event3 = new UserCreated(
            userId: $aggregateId,
            email: 'event3@example.com',
            name: 'Event 3',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        $this->eventStore->append($aggregateId, [$event1], expectedVersion: 0);
        $this->eventStore->append($aggregateId, [$event2], expectedVersion: 1);
        $this->eventStore->append($aggregateId, [$event3], expectedVersion: 2);

        // Act - Get events from version 1 onwards
        $events = $this->eventStore->getEventsForAggregateFromVersion($aggregateId, 1);

        // Assert
        self::assertCount(2, $events);
        self::assertInstanceOf(UserCreated::class, $events[0]);
        self::assertInstanceOf(UserCreated::class, $events[1]);
        self::assertSame('Event 2', $events[0]->getName());
        self::assertSame('Event 3', $events[1]->getName());
    }

    public function testGetEventsForNonExistentAggregateReturnsEmptyArray(): void
    {
        // Arrange
        $nonExistentId = '550e8400-e29b-41d4-a716-446655440099';

        // Act
        $events = $this->eventStore->getEventsForAggregate($nonExistentId);

        // Assert
        self::assertCount(0, $events);
    }

    public function testAppendWithEmptyEventsArrayDoesNothing(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440005';

        // Act
        $this->eventStore->append($aggregateId, [], expectedVersion: 0);

        // Assert
        $events = $this->eventStore->getEventsForAggregate($aggregateId);
        self::assertCount(0, $events);
    }

    public function testAppendMultipleEventsInSingleBatch(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440006';
        $events = [
            new UserCreated(
                userId: $aggregateId,
                email: 'user1@example.com',
                name: 'User 1',
                roles: ['ROLE_USER'],
                status: 'pending'
            ),
            new UserCreated(
                userId: $aggregateId,
                email: 'user2@example.com',
                name: 'User 2',
                roles: ['ROLE_USER'],
                status: 'pending'
            ),
            new UserCreated(
                userId: $aggregateId,
                email: 'user3@example.com',
                name: 'User 3',
                roles: ['ROLE_USER'],
                status: 'pending'
            ),
        ];

        // Act
        $this->eventStore->append($aggregateId, $events, expectedVersion: 0);

        // Assert
        $storedEvents = $this->eventStore->getEventsForAggregate($aggregateId);
        self::assertCount(3, $storedEvents);
    }

    public function testConcurrentAppendsThrowException(): void
    {
        // Arrange
        $aggregateId = '550e8400-e29b-41d4-a716-446655440007';
        $event1 = new UserCreated(
            userId: $aggregateId,
            email: 'test1@example.com',
            name: 'Test 1',
            roles: ['ROLE_USER'],
            status: 'pending'
        );
        $event2 = new UserCreated(
            userId: $aggregateId,
            email: 'test2@example.com',
            name: 'Test 2',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // First append succeeds
        $this->eventStore->append($aggregateId, [$event1], expectedVersion: 0);

        // Assert
        $this->expectException(EventStoreException::class);

        // Act - Second append with same expected version fails (optimistic locking)
        $this->eventStore->append($aggregateId, [$event2], expectedVersion: 0);
    }
}
