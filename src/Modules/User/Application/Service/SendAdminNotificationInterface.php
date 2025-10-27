<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Service;

use App\Modules\User\Application\Query\DTO\UserSummaryDTO;

/**
 * Send Admin Notification Interface (Application Port).
 *
 * Defines the contract for sending notification emails to administrators
 * when a new user registers.
 *
 * This is an Application-layer port that allows the Application layer
 * to remain independent of infrastructure details.
 *
 * Implementation: SendAdminNotification (Adapters layer)
 *
 * Benefits:
 * - Dependency Inversion Principle (DIP) compliance
 * - Application layer doesn't depend on Adapters
 * - Easy to mock in tests
 * - Can swap implementations (e.g., different email providers)
 */
interface SendAdminNotificationInterface
{
    /**
     * Create admin notification emails in outbox.
     *
     * All emails are created in a single transaction. The consumer will handle
     * sending them asynchronously, with automatic retry on failure.
     *
     * @param list<UserSummaryDTO> $admins       List of admin users
     * @param string               $newUserEmail New user's email
     * @param string               $newUserName  New user's name
     * @param string               $eventId      Unique event identifier (for idempotence)
     */
    public function __invoke(array $admins, string $newUserEmail, string $newUserName, string $eventId): void;
}
