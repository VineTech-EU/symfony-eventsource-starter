<?php

declare(strict_types=1);

namespace App\Modules\Notification\Adapters\Service;

use App\Modules\Notification\Application\Service\EmailSenderInterface;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Generic Email Sender (Adapter Implementation).
 *
 * Shared infrastructure for sending emails.
 * Used by all email services (SendWelcomeEmail, SendApprovalEmail, etc.).
 *
 * Responsibilities:
 * - Send email via Symfony Mailer
 * - Handle FROM address configuration
 * - Provide consistent email sending logic
 *
 * This is pure infrastructure - no business logic.
 *
 * Note: Not marked as 'final' to allow mocking in unit tests.
 */
readonly class EmailSender implements EmailSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply@example.com',
        private string $fromName = 'DDD Event Sourcing App',
    ) {}

    /**
     * Send an email.
     *
     * @param string        $to       Recipient email address
     * @param string        $subject  Email subject
     * @param RenderedEmail $rendered Rendered HTML and text content
     */
    public function send(string $to, string $subject, RenderedEmail $rendered): void
    {
        $email = (new Email())
            ->from(\sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($to)
            ->subject($subject)
            ->html($rendered->html)
            ->text($rendered->text)
        ;

        $this->mailer->send($email);
    }
}
