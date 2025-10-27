<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Service;

use App\Modules\Notification\Domain\ValueObject\RenderedEmail;

/**
 * Email Sender Interface (Application Port).
 *
 * Defines the contract for sending emails.
 * This is an Application-layer port that allows the Application layer
 * to remain independent of infrastructure details.
 *
 * Implementation: EmailSender (Adapters layer)
 *
 * Benefits:
 * - Dependency Inversion Principle (DIP) compliance
 * - Application layer doesn't depend on Adapters
 * - Easy to mock in tests
 * - Can swap implementations (e.g., MailgunSender, SendGridSender)
 */
interface EmailSenderInterface
{
    /**
     * Send an email.
     *
     * @param string        $to       Recipient email address
     * @param string        $subject  Email subject
     * @param RenderedEmail $rendered Rendered HTML and text content
     */
    public function send(string $to, string $subject, RenderedEmail $rendered): void;
}
