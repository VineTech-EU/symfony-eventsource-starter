<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

use Symfony\Component\Uid\Uuid;

abstract class DomainEvent
{
    private readonly string $eventId;
    private readonly \DateTimeImmutable $occurredOn;

    /**
     * Event metadata for tracing:
     * - correlationId: Links all events from same request
     * - causationId: The command/event that caused this event
     * - userId: Who triggered the action
     * - ipAddress: Where it came from.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    public function __construct()
    {
        $this->eventId = Uuid::v4()->toRfc4122();
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): static
    {
        $clone = clone $this;
        $clone->metadata = array_merge($clone->metadata, $metadata);

        return $clone;
    }

    /**
     * Returns the current version of this event schema.
     *
     * IMPORTANT: Override this method when the event schema changes.
     * - V1: Initial schema
     * - V2: Added new field / changed structure
     * - V3: Further changes
     *
     * Used by EventSerializer for upcasting logic.
     * When storedVersion < getEventVersion(), upcaster chain is triggered.
     */
    public static function getEventVersion(): int
    {
        return 1; // Override in child classes when schema changes
    }

    abstract public function getAggregateId(): string;

    /**
     * Returns the stable event name used for storage and routing.
     *
     * IMPORTANT: This name must NEVER change after deployment!
     * - Used for database storage (event_store.event_type)
     * - Used for EventTypeRegistry mapping
     * - Used for RabbitMQ routing
     *
     * Format: "module.action" (e.g., "user.created", "order.placed")
     */
    abstract public static function getEventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => static::getEventName(),
            'aggregate_id' => $this->getAggregateId(),
            'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s.u'),
            'metadata' => $this->metadata,
        ];
    }
}
