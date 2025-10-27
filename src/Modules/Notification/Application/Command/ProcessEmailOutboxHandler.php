<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Command;

use App\Modules\Notification\Application\Service\EmailSenderInterface;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Process Email Outbox Handler.
 *
 * Consumer that processes pending emails from the outbox and sends them.
 *
 * Workflow:
 * 1. Fetch pending emails (limit: 100 per batch)
 * 2. For each email:
 *    - Try to send via EmailSender
 *    - On success: markAsSent(), update DB
 *    - On failure: markAsFailed(), update DB, retry if < 5 attempts
 * 3. Log metrics (sent, failed, skipped)
 *
 * Benefits:
 * - Batch processing (efficient SMTP usage)
 * - Automatic retry (up to 5 attempts)
 * - Resilient (failed emails remain in DB)
 * - Monitorable (logs + DB status)
 * - Decoupled from domain handlers
 *
 * Scheduler triggers this every 30 seconds via ProcessEmailOutbox command.
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ProcessEmailOutboxHandler
{
    public function __construct(
        private EmailOutboxRepositoryInterface $outbox,
        private EmailSenderInterface $sender,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ProcessEmailOutbox $command): void
    {
        $pendingEmails = $this->outbox->findPending(limit: 100);

        if ([] === $pendingEmails) {
            $this->logger->debug('No pending emails in outbox');

            return;
        }

        $sentCount = 0;
        $failedCount = 0;
        $permanentlyFailedCount = 0;

        foreach ($pendingEmails as $email) {
            try {
                // Send email
                $renderedEmail = new RenderedEmail(
                    html: $email->getHtmlBody(),
                    text: $email->getTextBody() ?? ''
                );

                $this->sender->send(
                    $email->getRecipientEmail(),
                    $email->getSubject(),
                    $renderedEmail
                );

                // Mark as sent
                $email->markAsSent();
                $this->outbox->update($email);

                ++$sentCount;

                $this->logger->info('Outbox email sent', [
                    'email_id' => $email->getId(),
                    'event_id' => $email->getEventId(),
                    'email_type' => $email->getEmailType(),
                    'recipient' => $email->getRecipientEmail(),
                ]);
            } catch (\Throwable $e) {
                // Mark as failed (increments attempts)
                $email->markAsFailed($e->getMessage());
                $this->outbox->update($email);

                if ($email->canRetry()) {
                    ++$failedCount;

                    $this->logger->warning('Outbox email failed, will retry', [
                        'email_id' => $email->getId(),
                        'event_id' => $email->getEventId(),
                        'email_type' => $email->getEmailType(),
                        'recipient' => $email->getRecipientEmail(),
                        'attempts' => $email->getAttempts(),
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    ++$permanentlyFailedCount;

                    $this->logger->error('Outbox email permanently failed', [
                        'email_id' => $email->getId(),
                        'event_id' => $email->getEventId(),
                        'email_type' => $email->getEmailType(),
                        'recipient' => $email->getRecipientEmail(),
                        'attempts' => $email->getAttempts(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->logger->info('Outbox processing completed', [
            'total_processed' => \count($pendingEmails),
            'sent' => $sentCount,
            'failed_retry' => $failedCount,
            'failed_permanent' => $permanentlyFailedCount,
        ]);
    }
}
