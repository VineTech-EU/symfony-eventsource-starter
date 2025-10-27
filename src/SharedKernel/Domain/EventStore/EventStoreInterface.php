<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\EventStore;

use App\SharedKernel\Domain\DomainEvent;

interface EventStoreInterface
{
    /**
     * Append events to an aggregate's event stream.
     *
     * @param DomainEvent[] $events
     */
    public function append(string $aggregateId, array $events, int $expectedVersion): void;

    /**
     * Get all events for an aggregate, ordered by version.
     *
     * @return DomainEvent[]
     */
    public function getEventsForAggregate(string $aggregateId): array;

    /**
     * Get events for an aggregate from a specific version.
     *
     * @return DomainEvent[]
     */
    public function getEventsForAggregateFromVersion(string $aggregateId, int $fromVersion): array;
}
