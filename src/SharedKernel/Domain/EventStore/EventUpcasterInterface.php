<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\EventStore;

/**
 * Event Upcaster Interface.
 *
 * Upcasters handle event schema evolution by transforming old event versions
 * to new versions when loading from the event store.
 *
 * Example usage:
 * - UserCreated v1 had only 'email' and 'name'
 * - UserCreated v2 adds 'emailVerified' field (default false)
 * - UserCreatedUpcasterV1ToV2 transforms old events to add the missing field
 *
 * This allows you to change event schemas without breaking existing event streams.
 */
interface EventUpcasterInterface
{
    /**
     * Returns the event type this upcaster handles.
     *
     * @return string Event class name (e.g., 'UserCreated')
     */
    public function supports(): string;

    /**
     * Returns the version this upcaster upgrades FROM.
     */
    public function fromVersion(): int;

    /**
     * Returns the version this upcaster upgrades TO.
     */
    public function toVersion(): int;

    /**
     * Transform event data from old schema to new schema.
     *
     * @param array<string, mixed> $eventData Original event payload
     *
     * @return array<string, mixed> Transformed event payload
     */
    public function upcast(array $eventData): array;
}
