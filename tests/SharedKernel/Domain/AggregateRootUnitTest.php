<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Domain;

use App\SharedKernel\Domain\AggregateRoot;
use App\SharedKernel\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AggregateRoot base class.
 * Tests Event Sourcing mechanics: event recording, reconstitution, versioning.
 *
 * @internal
 *
 * @covers \App\SharedKernel\Domain\AggregateRoot
 */
final class AggregateRootUnitTest extends TestCase
{
    public function testRecordEventAddsEventToDomainEvents(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');
        $event = new TestEvent('test-123');

        // Act
        $aggregate->doSomething($event);

        // Assert
        $events = $aggregate->getDomainEvents();
        self::assertCount(1, $events);
        self::assertSame($event, $events[0]);
    }

    public function testRecordEventIncrementsVersion(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');
        self::assertSame(0, $aggregate->getVersion());

        // Act
        $aggregate->doSomething(new TestEvent('test-123'));

        // Assert
        self::assertSame(1, $aggregate->getVersion());
    }

    public function testRecordEventAppliesEventToAggregate(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');
        $event = new TestEvent('test-123');

        // Act
        $aggregate->doSomething($event);

        // Assert
        self::assertSame(1, $aggregate->getAppliedEventCount());
    }

    public function testPullDomainEventsReturnsEventsAndClearsThem(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');
        $aggregate->doSomething(new TestEvent('test-123'));
        $aggregate->doSomething(new TestEvent('test-123'));

        // Act
        $events = $aggregate->pullDomainEvents();

        // Assert
        self::assertCount(2, $events);
        self::assertCount(0, $aggregate->getDomainEvents());
    }

    public function testGetDomainEventsDoesNotClearEvents(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');
        $aggregate->doSomething(new TestEvent('test-123'));

        // Act
        $events1 = $aggregate->getDomainEvents();
        $events2 = $aggregate->getDomainEvents();

        // Assert
        self::assertCount(1, $events1);
        self::assertCount(1, $events2);
    }

    public function testReconstituteCreatesAggregateFromEvents(): void
    {
        // Arrange
        $events = [
            new TestEvent('test-123'),
            new TestEvent('test-123'),
            new TestEvent('test-123'),
        ];

        // Act
        $aggregate = TestAggregate::reconstitute($events);

        // Assert - reconstitute creates instance without constructor params
        self::assertSame('test-id', $aggregate->getId()); // Default value
        self::assertSame(3, $aggregate->getVersion());
        self::assertSame(3, $aggregate->getAppliedEventCount());
    }

    public function testReconstituteDoesNotRecordNewEvents(): void
    {
        // Arrange
        $events = [
            new TestEvent('test-123'),
            new TestEvent('test-123'),
        ];

        // Act
        $aggregate = TestAggregate::reconstitute($events);

        // Assert
        self::assertCount(0, $aggregate->getDomainEvents());
    }

    public function testReconstituteWithEmptyEventsCreatesAggregateWithVersionZero(): void
    {
        // Act
        $aggregate = TestAggregate::reconstitute([]);

        // Assert
        self::assertSame(0, $aggregate->getVersion());
        self::assertSame(0, $aggregate->getAppliedEventCount());
    }

    public function testMultipleRecordEventsIncrementsVersionCorrectly(): void
    {
        // Arrange
        $aggregate = new TestAggregate('test-123');

        // Act
        $aggregate->doSomething(new TestEvent('test-123'));
        $aggregate->doSomething(new TestEvent('test-123'));
        $aggregate->doSomething(new TestEvent('test-123'));

        // Assert
        self::assertSame(3, $aggregate->getVersion());
        self::assertCount(3, $aggregate->getDomainEvents());
    }

    public function testVersionStartsAtZero(): void
    {
        // Arrange & Act
        $aggregate = new TestAggregate('test-123');

        // Assert
        self::assertSame(0, $aggregate->getVersion());
    }
}

/**
 * Test implementation of AggregateRoot for testing purposes.
 */
final class TestAggregate extends AggregateRoot
{
    private int $appliedEventCount = 0;

    public function __construct(
        private readonly string $id = 'test-id',
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function doSomething(TestEvent $event): void
    {
        $this->recordEvent($event);
    }

    public function getAppliedEventCount(): int
    {
        return $this->appliedEventCount;
    }

    protected function apply(DomainEvent $event): void
    {
        if ($event instanceof TestEvent) {
            ++$this->appliedEventCount;
        }
    }
}

/**
 * Test implementation of DomainEvent for testing purposes.
 */
final class TestEvent extends DomainEvent
{
    public function __construct(
        private readonly string $aggregateId,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public static function getEventName(): string
    {
        return 'test.event';
    }
}
