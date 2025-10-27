<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Email\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\User\Application\Service\SendWelcomeEmailInterface;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Send Welcome Email (Adapter Implementation).
 *
 * Sends a welcome email to a newly registered user via Transactional Outbox Pattern.
 *
 * Usage:
 * $sendWelcomeEmail('john@example.com', 'John Doe', 'event-uuid');
 *
 * Benefits of Outbox Pattern:
 * - Transactional: Email creation in same DB transaction as event
 * - Idempotent: No duplicate emails (unique constraint on event_id + recipient)
 * - Resilient: Failed emails remain in outbox for retry
 * - Monitorable: Easy to query pending/failed emails
 * - Decoupled: Consumer handles actual sending asynchronously
 *
 * Note: Not marked as 'final' to allow mocking in unit tests.
 */
readonly class SendWelcomeEmail implements SendWelcomeEmailInterface
{
    public function __construct(
        private EmailOutboxRepositoryInterface $outbox,
        private EmailTemplateRenderer $renderer,
        private TranslatorInterface $translator,
    ) {}

    /**
     * Create welcome email in outbox.
     *
     * Email will be sent asynchronously by ProcessEmailOutboxHandler.
     *
     * @param string $recipientEmail User's email address
     * @param string $recipientName  User's name
     * @param string $eventId        Event ID for idempotence
     */
    public function __invoke(string $recipientEmail, string $recipientName, string $eventId): void
    {
        $subject = $this->translator->trans('email.welcome.header', [], 'emails');

        $rendered = $this->renderer->render('@user_emails/user/welcome.html.twig', [
            'name' => $recipientName,
        ]);

        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: $eventId,
            emailType: 'welcome',
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            subject: $subject,
            htmlBody: $rendered->html,
            textBody: $rendered->text,
        );

        $this->outbox->save($email);
    }
}
