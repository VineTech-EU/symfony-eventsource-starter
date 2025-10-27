<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Domain\DomainEvent;

/**
 * Registry mapping event names to their FQCN.
 *
 * Populated automatically via CompilerPass by scanning all DomainEvent classes.
 * Enables refactoring-safe event storage (store stable event name, not FQCN).
 *
 * Example:
 * - "user.created" => App\Modules\User\Domain\Event\UserCreated
 * - "user.email_changed" => App\Modules\User\Domain\Event\UserEmailChanged
 */
final class EventTypeRegistry
{
    /**
     * @var array<string, class-string<DomainEvent>>
     */
    private array $eventMap = [];

    /**
     * Register an event type.
     *
     * @param class-string<DomainEvent> $eventClass
     */
    public function register(string $eventName, string $eventClass): void
    {
        if (isset($this->eventMap[$eventName])) {
            throw new \RuntimeException(\sprintf(
                'Event name "%s" is already registered to "%s", cannot register "%s"',
                $eventName,
                $this->eventMap[$eventName],
                $eventClass
            ));
        }

        $this->eventMap[$eventName] = $eventClass;
    }

    /**
     * Get event class by event name.
     *
     * @return class-string<DomainEvent>
     *
     * @throws \InvalidArgumentException If event name is not registered
     */
    public function getEventClass(string $eventName): string
    {
        if (!isset($this->eventMap[$eventName])) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown event name: "%s". Registered events: %s',
                $eventName,
                implode(', ', array_keys($this->eventMap))
            ));
        }

        return $this->eventMap[$eventName];
    }

    /**
     * Check if event name is registered.
     */
    public function has(string $eventName): bool
    {
        return isset($this->eventMap[$eventName]);
    }

    /**
     * Get all registered event names.
     *
     * @return array<string>
     */
    public function getRegisteredEventNames(): array
    {
        return array_keys($this->eventMap);
    }
}
