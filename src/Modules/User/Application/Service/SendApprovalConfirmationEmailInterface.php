<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Service;

/**
 * Send Approval Confirmation Email Interface (Application Port).
 *
 * Defines the contract for sending approval confirmation emails to users
 * when their account is approved.
 *
 * This is an Application-layer port that allows the Application layer
 * to remain independent of infrastructure details.
 *
 * Implementation: SendApprovalConfirmationEmail (Adapters layer)
 *
 * Benefits:
 * - Dependency Inversion Principle (DIP) compliance
 * - Application layer doesn't depend on Adapters
 * - Easy to mock in tests
 * - Can swap implementations (e.g., different email providers)
 */
interface SendApprovalConfirmationEmailInterface
{
    /**
     * Create approval confirmation email in outbox.
     *
     * Email will be sent asynchronously by ProcessEmailOutboxHandler.
     *
     * @param string $recipientEmail User's email address
     * @param string $recipientName  User's name
     * @param string $eventId        Event ID for idempotence
     */
    public function __invoke(string $recipientEmail, string $recipientName, string $eventId): void;
}
