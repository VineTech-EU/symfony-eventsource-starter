<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain;

abstract class AggregateRoot
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    private int $version = 0;

    /**
     * Reconstitute aggregate from event stream.
     *
     * @param DomainEvent[] $events
     */
    public static function reconstitute(array $events): static
    {
        /** @phpstan-ignore new.static */
        $instance = new static();

        foreach ($events as $event) {
            $instance->apply($event);
            ++$instance->version;
        }

        return $instance;
    }

    /**
     * @return DomainEvent[]
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * @return DomainEvent[]
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    abstract public function getId(): string;

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
        $this->apply($event);
        ++$this->version;
    }

    /**
     * Apply event to change aggregate state (for event sourcing reconstruction).
     */
    abstract protected function apply(DomainEvent $event): void;
}
