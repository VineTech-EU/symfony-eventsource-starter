<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Service;

/**
 * Send Welcome Email Interface (Application Port).
 *
 * Defines the contract for sending welcome emails to newly registered users.
 * This is an Application-layer port that allows the Application layer
 * to remain independent of infrastructure details.
 *
 * Implementation: SendWelcomeEmail (Adapters layer)
 *
 * Benefits:
 * - Dependency Inversion Principle (DIP) compliance
 * - Application layer doesn't depend on Adapters
 * - Easy to mock in tests
 * - Can swap implementations (e.g., different email providers)
 */
interface SendWelcomeEmailInterface
{
    /**
     * Create welcome email in outbox.
     *
     * Email will be sent asynchronously by ProcessEmailOutboxHandler.
     *
     * @param string $recipientEmail User's email address
     * @param string $recipientName  User's name
     * @param string $eventId        Event ID for idempotence
     */
    public function __invoke(string $recipientEmail, string $recipientName, string $eventId): void;
}
