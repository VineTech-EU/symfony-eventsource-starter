<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\EventStore\Upcaster;

use App\SharedKernel\Domain\EventStore\EventUpcasterInterface;

/**
 * Example Event Upcaster: UserCreated V1 → V2.
 *
 * PURPOSE:
 * Demonstrates how to handle event schema evolution when UserCreated event changes.
 *
 * SCENARIO:
 * - V1: UserCreated had only 'email' field
 * - V2: UserCreated now requires 'emailVerified' boolean field
 *
 * ACTIVATION:
 * This upcaster is currently DISABLED (not tagged in services.yaml).
 * To activate:
 * 1. Uncomment in config/services.yaml
 * 2. Tag with 'app.event_upcaster'
 *
 * TESTING:
 * Before deploying, test with real event store data:
 * 1. Load old events from production backup
 * 2. Run replay with upcaster active
 * 3. Verify projections rebuild correctly
 *
 * WARNING:
 * - Never modify this upcaster once deployed to production
 * - Create V2ToV3 for further changes
 * - Keep old upcasters for historical replay capability
 *
 * @see https://buildplease.com/pages/fpc-6/ Event Versioning Pattern
 */
final readonly class UserCreatedV1ToV2Upcaster implements EventUpcasterInterface
{
    public function supports(): string
    {
        // Event type this upcaster handles
        return 'user.created';
    }

    public function fromVersion(): int
    {
        // This upcaster upgrades FROM version 1
        return 1;
    }

    public function toVersion(): int
    {
        // This upcaster upgrades TO version 2
        return 2;
    }

    /**
     * @param array<string, mixed> $eventData
     *
     * @return array<string, mixed>
     */
    public function upcast(array $eventData): array
    {
        // Extract current payload
        $payload = $eventData;

        // Add new field with sensible default
        // V1 users were not verified by default
        $payload['emailVerified'] = false;

        // Optionally transform existing fields
        // Example: normalize email to lowercase
        if (isset($payload['email']) && \is_string($payload['email'])) {
            $payload['email'] = strtolower($payload['email']);
        }

        return $payload;
    }
}
