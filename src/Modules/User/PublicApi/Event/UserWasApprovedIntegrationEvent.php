<?php

declare(strict_types=1);

namespace App\Modules\User\PublicApi\Event;

/**
 * Integration Event: User Was Approved.
 *
 * Fired when a user's status changes to "approved".
 * Other modules might want to:
 * - Create billing account
 * - Send notification
 * - Grant access to features
 */
final readonly class UserWasApprovedIntegrationEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
        public \DateTimeImmutable $occurredOn,
    ) {}
}
