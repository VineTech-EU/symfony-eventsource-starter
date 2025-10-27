<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Entity;

use App\Modules\Notification\Domain\ValueObject\EmailStatus;

/**
 * Email Outbox.
 *
 * Represents an email to be sent via the Transactional Outbox Pattern.
 *
 * Benefits:
 * - Transactional: Email creation in same DB transaction as domain event
 * - Idempotent: Unique constraint on (event_id, recipient_email, email_type)
 * - Auditable: Complete history of all emails (sent, failed, pending)
 * - Resilient: Failed emails remain in outbox for retry
 * - Monitorable: Easy to query pending/failed emails
 *
 * Lifecycle:
 * 1. Created with status=PENDING
 * 2. Consumer picks it up and attempts to send
 * 3. On success: markAsSent() → status=SENT
 * 4. On failure: markAsFailed() → status=FAILED (after max retries)
 */
final class EmailOutbox
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private string $id,
        private string $eventId,
        private string $emailType,
        private string $recipientEmail,
        private ?string $recipientName,
        private string $subject,
        private string $htmlBody,
        private ?string $textBody,
        private EmailStatus $status,
        private int $attempts,
        private ?string $lastError,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $sentAt,
    ) {}

    /**
     * Create a new email for the outbox.
     *
     * Note: ID generation is NOT the responsibility of this Domain Entity.
     * Use SymfonyUuid::generate() in the Application/Adapters layer to create
     * a unique ID, then pass it here.
     *
     * @param string $id Unique identifier (UUID v7 recommended for sortability)
     */
    public static function create(
        string $id,
        string $eventId,
        string $emailType,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $htmlBody,
        ?string $textBody,
    ): self {
        return new self(
            id: $id,
            eventId: $eventId,
            emailType: $emailType,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            subject: $subject,
            htmlBody: $htmlBody,
            textBody: $textBody,
            status: EmailStatus::PENDING,
            attempts: 0,
            lastError: null,
            createdAt: new \DateTimeImmutable(),
            sentAt: null,
        );
    }

    public function markAsSent(): void
    {
        $this->status = EmailStatus::SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->lastError = null;
    }

    public function markAsFailed(string $error): void
    {
        ++$this->attempts;
        $this->lastError = $error;

        // After max attempts, mark as permanently failed
        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $this->status = EmailStatus::FAILED;
        }
    }

    public function canRetry(): bool
    {
        return $this->status === EmailStatus::PENDING && $this->attempts < self::MAX_ATTEMPTS;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEmailType(): string
    {
        return $this->emailType;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    public function getStatus(): EmailStatus
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }
}
