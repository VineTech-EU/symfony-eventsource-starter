<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Domain\DomainEvent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Event Serializer using Symfony Serializer and EventTypeRegistry.
 *
 * Key features:
 * - Uses EventTypeRegistry for event name → FQCN mapping (auto-discovered via CompilerPass)
 * - Stores stable event names in DB (refactoring-safe)
 * - Symfony Serializer handles denormalization automatically
 * - Upcasting integrated for event schema evolution
 * - Type-safe with proper validation
 */
final readonly class EventSerializer
{
    public function __construct(
        private DenormalizerInterface $denormalizer,
        private EventUpcasterChain $upcasterChain,
        private EventTypeRegistry $eventTypeRegistry,
    ) {}

    /**
     * Serialize domain event to array for storage.
     *
     * @return array<string, mixed>
     */
    public function serialize(DomainEvent $event): array
    {
        return $event->toArray();
    }

    /**
     * Deserialize event from storage with automatic upcasting.
     *
     * @param string               $eventName     Stable event name from DB (e.g., "user.created")
     * @param array<string, mixed> $payload       Event data from storage
     * @param int                  $storedVersion Version of the stored event
     *
     * @throws \InvalidArgumentException If event name is not registered
     */
    public function deserialize(string $eventName, array $payload, int $storedVersion = 1): DomainEvent
    {
        // Get FQCN from registry (auto-discovered via CompilerPass)
        $eventClass = $this->eventTypeRegistry->getEventClass($eventName);

        // Get target version from event class
        $targetVersion = $this->getEventTargetVersion($eventClass);

        // Upcast if needed (old stored version → current version)
        if ($storedVersion < $targetVersion) {
            $shortEventName = $this->getEventNameFromClass($eventClass);
            $payload = $this->upcasterChain->upcast(
                $shortEventName,
                $storedVersion,
                $payload,
                $targetVersion
            );
        }

        // Use Symfony Serializer to denormalize array → DomainEvent
        $event = $this->denormalizer->denormalize(
            $payload,
            $eventClass,
            null,
            []
        );

        if (!$event instanceof DomainEvent) {
            throw new \RuntimeException(\sprintf(
                'Denormalization failed: expected DomainEvent, got %s',
                get_debug_type($event)
            ));
        }

        return $event;
    }

    /**
     * Get the target version for an event class.
     * Calls static getEventVersion() method on the event class.
     *
     * @param class-string<DomainEvent> $eventClass
     */
    private function getEventTargetVersion(string $eventClass): int
    {
        return $eventClass::getEventVersion();
    }

    /**
     * Extract short event name from FQCN.
     * Example: App\Modules\User\Domain\Event\UserCreated → UserCreated.
     *
     * @param class-string<DomainEvent> $eventClass
     */
    private function getEventNameFromClass(string $eventClass): string
    {
        $parts = explode('\\', $eventClass);

        return end($parts);
    }
}
