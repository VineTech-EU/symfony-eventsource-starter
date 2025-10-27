<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Email\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;
use App\Modules\User\Application\Service\SendAdminNotificationInterface;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Send Admin Notification (Adapter Implementation).
 *
 * Sends notification to admins when a new user registers via Transactional Outbox Pattern.
 *
 * Usage:
 * $sendAdminNotification($admins, 'john@example.com', 'John Doe', 'event-uuid');
 *
 * Benefits of Outbox Pattern:
 * - Transactional: All emails created in same DB transaction as event
 * - Idempotent: Unique constraint prevents duplicates (event_id + recipient + type)
 * - Resilient: Failed emails remain in outbox for retry
 * - Batch processing: Consumer can send all admin emails in one batch
 * - Monitorable: Easy to query pending/failed emails
 * - Decoupled: Consumer handles actual sending asynchronously
 *
 * Note: Not marked as 'final' to allow mocking in unit tests.
 */
readonly class SendAdminNotification implements SendAdminNotificationInterface
{
    public function __construct(
        private EmailOutboxRepositoryInterface $outbox,
        private EmailTemplateRenderer $renderer,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Create admin notification emails in outbox.
     *
     * All emails are created in a single transaction. The consumer will handle
     * sending them asynchronously, with automatic retry on failure.
     *
     * Benefits:
     * - Transactional guarantee: All or nothing
     * - Idempotent: Unique constraint on (event_id, recipient, type)
     * - No spam: Consumer skips already-sent emails
     * - Batch sending: Consumer can optimize SMTP connections
     *
     * @param list<UserSummaryDTO> $admins       List of admin users
     * @param string               $newUserEmail New user's email
     * @param string               $newUserName  New user's name
     * @param string               $eventId      Unique event identifier (for idempotence)
     */
    public function __invoke(array $admins, string $newUserEmail, string $newUserName, string $eventId): void
    {
        if ([] === $admins) {
            $this->logger->info('No admins to notify about new user registration', [
                'new_user_email' => $newUserEmail,
                'event_id' => $eventId,
            ]);

            return;
        }

        $subject = $this->translator->trans('email.admin.header', [], 'emails') . ' - '
            . $this->translator->trans('email.admin.status_pending_value', [], 'emails');

        $rendered = $this->renderer->render('@user_emails/user/admin_notification.html.twig', [
            'newUserEmail' => $newUserEmail,
            'newUserName' => $newUserName,
        ]);

        // Create outbox entry for each admin
        foreach ($admins as $admin) {
            $email = EmailOutbox::create(
                id: SymfonyUuid::generate()->toString(),
                eventId: $eventId,
                emailType: 'admin_notification',
                recipientEmail: $admin->email,
                recipientName: $admin->name,
                subject: $subject,
                htmlBody: $rendered->html,
                textBody: $rendered->text,
            );

            // Idempotent: ON CONFLICT DO NOTHING
            $this->outbox->save($email);
        }

        $this->logger->info('Admin notification emails created in outbox', [
            'total_admins' => \count($admins),
            'new_user_email' => $newUserEmail,
            'event_id' => $eventId,
        ]);
    }
}
