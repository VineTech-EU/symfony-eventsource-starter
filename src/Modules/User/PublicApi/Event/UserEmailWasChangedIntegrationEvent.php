<?php

declare(strict_types=1);

namespace App\Modules\User\PublicApi\Event;

/**
 * Integration Event: User Email Was Changed.
 *
 * Fired when a user changes their email address.
 * Other modules might want to:
 * - Update email in their read models
 * - Send confirmation email
 * - Update notification preferences
 */
final readonly class UserEmailWasChangedIntegrationEvent
{
    public function __construct(
        public string $userId,
        public string $oldEmail,
        public string $newEmail,
        public \DateTimeImmutable $occurredOn,
    ) {}
}
