<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(name: 'idx_aggregate_version', columns: ['aggregate_id', 'version'])]
#[ORM\Index(name: 'idx_aggregate_id', columns: ['aggregate_id'])]
#[ORM\Index(name: 'idx_event_type', columns: ['event_type'])]
#[ORM\UniqueConstraint(name: 'uniq_aggregate_version', columns: ['aggregate_id', 'version'])]
class EventStoreEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $eventId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $aggregateType;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    /**
     * Event schema version for handling event evolution.
     * When event structure changes, increment this version and create an upcaster.
     */
    #[ORM\Column(type: 'integer')]
    private int $eventVersion;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $payload;

    /**
     * Event metadata for tracing and debugging:
     * - correlationId: Links all events from same request
     * - causationId: The command/event that caused this event
     * - userId: Who triggered the action
     * - ipAddress: Where it came from
     * - userAgent: Client information
     *
     * @var null|array<string, mixed>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'integer')]
    private int $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredOn;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedOn;

    /**
     * @param array<string, mixed>      $payload
     * @param null|array<string, mixed> $metadata
     */
    public function __construct(
        string $eventId,
        string $aggregateId,
        string $aggregateType,
        string $eventType,
        array $payload,
        int $version,
        \DateTimeImmutable $occurredOn,
        ?array $metadata = null,
        int $eventVersion = 1,
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
        $this->eventType = $eventType;
        $this->eventVersion = $eventVersion;
        $this->payload = $payload;
        $this->version = $version;
        $this->occurredOn = $occurredOn;
        $this->recordedOn = new \DateTimeImmutable();
        $this->metadata = $metadata;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getRecordedOn(): \DateTimeImmutable
    {
        return $this->recordedOn;
    }

    public function getEventVersion(): int
    {
        return $this->eventVersion;
    }
}
