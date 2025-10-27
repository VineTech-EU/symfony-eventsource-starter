<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Domain;

use App\SharedKernel\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DomainEvent base class.
 * Tests event identity, metadata, and immutability.
 *
 * @internal
 *
 * @covers \App\SharedKernel\Domain\DomainEvent
 */
final class DomainEventUnitTest extends TestCase
{
    public function testEventHasUniqueEventId(): void
    {
        // Act
        $event1 = new ConcreteDomainEvent('aggregate-1');
        $event2 = new ConcreteDomainEvent('aggregate-1');

        // Assert
        self::assertNotSame($event1->getEventId(), $event2->getEventId());
    }

    public function testEventIdIsValidUuidV4(): void
    {
        // Act
        $event = new ConcreteDomainEvent('aggregate-1');

        // Assert
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $event->getEventId()
        );
    }

    public function testEventHasOccurredOnTimestamp(): void
    {
        // Arrange
        $before = new \DateTimeImmutable();

        // Act
        $event = new ConcreteDomainEvent('aggregate-1');
        $after = new \DateTimeImmutable();

        // Assert
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
        self::assertGreaterThanOrEqual($before, $event->getOccurredOn());
        self::assertLessThanOrEqual($after, $event->getOccurredOn());
    }

    public function testGetMetadataReturnsEmptyArrayByDefault(): void
    {
        // Act
        $event = new ConcreteDomainEvent('aggregate-1');

        // Assert
        self::assertSame([], $event->getMetadata());
    }

    public function testWithMetadataAddsMetadata(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $metadata = [
            'correlationId' => 'corr-123',
            'causationId' => 'cause-456',
        ];

        // Act
        $eventWithMetadata = $event->withMetadata($metadata);

        // Assert
        self::assertSame($metadata, $eventWithMetadata->getMetadata());
    }

    public function testWithMetadataMergesMetadata(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $metadata1 = ['correlationId' => 'corr-123'];
        $metadata2 = ['causationId' => 'cause-456'];

        // Act
        $eventWithMetadata = $event
            ->withMetadata($metadata1)
            ->withMetadata($metadata2)
        ;

        // Assert
        self::assertSame([
            'correlationId' => 'corr-123',
            'causationId' => 'cause-456',
        ], $eventWithMetadata->getMetadata());
    }

    public function testWithMetadataDoesNotMutateOriginalEvent(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $metadata = ['correlationId' => 'corr-123'];

        // Act
        $eventWithMetadata = $event->withMetadata($metadata);

        // Assert
        self::assertSame([], $event->getMetadata());
        self::assertSame($metadata, $eventWithMetadata->getMetadata());
    }

    public function testGetEventVersionReturnsDefaultVersion(): void
    {
        // Assert - Static call on class
        self::assertSame(1, ConcreteDomainEvent::getEventVersion());
    }

    public function testToArrayIncludesAllBaseFields(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-123');
        $metadata = ['correlationId' => 'corr-456'];
        $eventWithMetadata = $event->withMetadata($metadata);

        // Act
        $array = $eventWithMetadata->toArray();

        // Assert
        self::assertArrayHasKey('event_id', $array);
        self::assertArrayHasKey('event_name', $array);
        self::assertArrayHasKey('aggregate_id', $array);
        self::assertArrayHasKey('occurred_on', $array);
        self::assertArrayHasKey('metadata', $array);

        self::assertSame($eventWithMetadata->getEventId(), $array['event_id']);
        self::assertSame('concrete.event', $array['event_name']);
        self::assertSame('aggregate-123', $array['aggregate_id']);
        self::assertSame($metadata, $array['metadata']);
    }

    public function testToArrayFormatsOccurredOnCorrectly(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');

        // Act
        $array = $event->toArray();

        // Assert
        self::assertIsString($array['occurred_on']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/',
            $array['occurred_on']
        );
    }

    public function testEventIsImmutable(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $eventId = $event->getEventId();
        $occurredOn = $event->getOccurredOn();

        // Act - Multiple calls should return same values
        $eventId2 = $event->getEventId();
        $occurredOn2 = $event->getOccurredOn();

        // Assert
        self::assertSame($eventId, $eventId2);
        self::assertSame($occurredOn, $occurredOn2);
    }

    public function testMetadataCanContainNestedArrays(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $metadata = [
            'user' => [
                'id' => 'user-123',
                'email' => 'test@example.com',
            ],
            'request' => [
                'ip' => '127.0.0.1',
                'userAgent' => 'PHPUnit',
            ],
        ];

        // Act
        $eventWithMetadata = $event->withMetadata($metadata);

        // Assert
        self::assertSame($metadata, $eventWithMetadata->getMetadata());
    }

    public function testWithMetadataOverwritesExistingKeys(): void
    {
        // Arrange
        $event = new ConcreteDomainEvent('aggregate-1');
        $metadata1 = ['correlationId' => 'old-value'];
        $metadata2 = ['correlationId' => 'new-value'];

        // Act
        $eventWithMetadata = $event
            ->withMetadata($metadata1)
            ->withMetadata($metadata2)
        ;

        // Assert
        self::assertSame(['correlationId' => 'new-value'], $eventWithMetadata->getMetadata());
    }
}

/**
 * Concrete implementation of DomainEvent for testing purposes.
 */
final class ConcreteDomainEvent extends DomainEvent
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
        return 'concrete.event';
    }
}
