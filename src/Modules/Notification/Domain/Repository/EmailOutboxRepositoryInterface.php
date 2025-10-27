<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\Repository;

use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\ValueObject\EmailStatus;

/**
 * Email Outbox Repository Interface.
 *
 * Key operations:
 * - save(): Insert new email (idempotent via unique constraint)
 * - findPending(): Get emails ready to send (consumer query)
 * - findByEventId(): Get all emails for a specific event (debugging)
 * - countByStatus(): Monitoring queries
 */
interface EmailOutboxRepositoryInterface
{
    /**
     * Save email to outbox.
     *
     * Idempotent: Unique constraint on (event_id, recipient_email, email_type)
     * ensures no duplicate emails even if called multiple times.
     */
    public function save(EmailOutbox $email): void;

    /**
     * Update existing email (after send attempt).
     */
    public function update(EmailOutbox $email): void;

    /**
     * Find pending emails to send (consumer query).
     *
     * Orders by created_at to ensure FIFO processing.
     *
     * @return list<EmailOutbox>
     */
    public function findPending(int $limit = 100): array;

    /**
     * Find all emails for a specific event (debugging).
     *
     * @return list<EmailOutbox>
     */
    public function findByEventId(string $eventId): array;

    /**
     * Count emails by status (monitoring).
     */
    public function countByStatus(EmailStatus $status): int;

    /**
     * Get oldest pending email (monitoring).
     */
    public function getOldestPending(): ?EmailOutbox;
}
