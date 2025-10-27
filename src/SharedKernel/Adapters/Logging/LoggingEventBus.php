<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Logging;

use App\SharedKernel\Application\Bus\EventBusInterface;
use App\SharedKernel\Domain\DomainEvent;
use Psr\Log\LoggerInterface;

/**
 * Logging Event Bus Decorator.
 *
 * Wraps the real event bus to add structured logging.
 * This is the DECORATOR pattern - adds logging without polluting domain logic.
 *
 * Logs every event dispatched with:
 * - STATIC message: "Domain event dispatched"
 * - DYNAMIC context: event type, aggregate ID, payload, etc.
 *
 * In Datadog/Sentry:
 * - All events grouped under same message
 * - Filterable by event_type, aggregate_id, etc.
 */
final readonly class LoggingEventBus implements EventBusInterface
{
    public function __construct(
        private EventBusInterface $decorated,
        private LoggerInterface $logger,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        // Log BEFORE dispatch (in case of failure)
        $this->logger->info('Domain event dispatched', [  // ← STATIC message
            'event_type' => $event::getEventName(),
            'event_class' => $event::class,
            'aggregate_id' => $event->getAggregateId(),
            'event_id' => $event->getEventId(),
            'occurred_on' => $event->getOccurredOn()->format(\DateTimeInterface::ATOM),
            'payload' => $event->toArray(),
        ]);

        try {
            $this->decorated->dispatch($event);

            // Log success
            $this->logger->debug('Domain event dispatch succeeded', [
                'event_type' => $event::getEventName(),
                'aggregate_id' => $event->getAggregateId(),
            ]);
        } catch (\Throwable $e) {
            // Log failure
            $this->logger->error('Domain event dispatch failed', [
                'event_type' => $event::getEventName(),
                'aggregate_id' => $event->getAggregateId(),
                'error_message' => $e->getMessage(),
                'error_class' => $e::class,
            ]);

            throw $e;
        }
    }

    public function dispatchAll(array $events): void
    {
        $this->logger->info('Domain events batch dispatched', [  // ← STATIC message
            'events_count' => \count($events),
            'event_types' => array_map(
                static fn (DomainEvent $e) => $e::getEventName(),
                $events
            ),
        ]);

        try {
            $this->decorated->dispatchAll($events);

            $this->logger->debug('Domain events batch dispatch succeeded', [
                'events_count' => \count($events),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Domain events batch dispatch failed', [
                'events_count' => \count($events),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
