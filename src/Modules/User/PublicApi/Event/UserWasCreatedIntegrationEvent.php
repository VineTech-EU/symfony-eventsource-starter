<?php

declare(strict_types=1);

namespace App\Modules\User\PublicApi\Event;

/**
 * Integration Event: User Was Created.
 *
 * This is a PUBLIC event that other modules can listen to.
 * It represents a simplified, stable API for cross-module communication.
 *
 * Rules:
 * - Contains only essential data other modules need
 * - Schema should be stable (breaking changes require versioning)
 * - No domain objects (only primitives)
 * - Async communication via RabbitMQ
 */
final readonly class UserWasCreatedIntegrationEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
        public string $role,
        public string $status,
        public \DateTimeImmutable $occurredOn,
    ) {}
}
