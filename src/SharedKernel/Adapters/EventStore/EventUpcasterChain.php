<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Domain\EventStore\EventUpcasterInterface;

/**
 * Event Upcaster Chain.
 *
 * Applies multiple upcasters in sequence to transform an event
 * from an old version to the latest version.
 *
 * Example:
 * - Event stored as v1
 * - Upcaster v1→v2 transforms it to v2
 * - Upcaster v2→v3 transforms it to v3
 * - Final result: v3 event ready for deserialization
 */
final class EventUpcasterChain
{
    /**
     * @var array<string, array<int, EventUpcasterInterface>>
     */
    private array $upcasters = [];

    /**
     * @param iterable<EventUpcasterInterface> $upcasters
     */
    public function __construct(iterable $upcasters)
    {
        foreach ($upcasters as $upcaster) {
            $eventType = $upcaster->supports();
            $fromVersion = $upcaster->fromVersion();

            if (!isset($this->upcasters[$eventType])) {
                $this->upcasters[$eventType] = [];
            }

            $this->upcasters[$eventType][$fromVersion] = $upcaster;
        }
    }

    /**
     * Upcast event data from stored version to latest version.
     *
     * @param array<string, mixed> $eventData
     *
     * @return array<string, mixed>
     */
    public function upcast(string $eventType, int $storedVersion, array $eventData, int $targetVersion): array
    {
        // If already at target version, no upcasting needed
        if ($storedVersion === $targetVersion) {
            return $eventData;
        }

        // No upcasters registered for this event type
        if (!isset($this->upcasters[$eventType])) {
            return $eventData;
        }

        $currentVersion = $storedVersion;
        $currentData = $eventData;

        // Apply upcasters sequentially: v1 → v2 → v3 → ...
        while ($currentVersion < $targetVersion) {
            if (!isset($this->upcasters[$eventType][$currentVersion])) {
                // No upcaster found for this version transition
                // This is OK if the event schema didn't change
                ++$currentVersion;

                continue;
            }

            $upcaster = $this->upcasters[$eventType][$currentVersion];
            $currentData = $upcaster->upcast($currentData);
            $currentVersion = $upcaster->toVersion();
        }

        return $currentData;
    }
}
