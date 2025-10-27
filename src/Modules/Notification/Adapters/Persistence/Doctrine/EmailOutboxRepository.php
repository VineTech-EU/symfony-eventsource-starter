<?php

declare(strict_types=1);

namespace App\Modules\Notification\Adapters\Persistence\Doctrine;

use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\EmailStatus;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

/**
 * Doctrine Email Outbox Repository.
 *
 * Persistence layer for EmailOutbox using raw SQL for performance.
 */
readonly class EmailOutboxRepository implements EmailOutboxRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception on database error
     */
    public function save(EmailOutbox $email): void
    {
        // Use INSERT ... ON CONFLICT DO NOTHING for idempotence
        $this->connection->executeStatement(
            '
            INSERT INTO email_outbox (
                id, event_id, email_type, recipient_email, recipient_name,
                subject, html_body, text_body, status, attempts, last_error,
                created_at, sent_at
            ) VALUES (
                :id, :event_id, :email_type, :recipient_email, :recipient_name,
                :subject, :html_body, :text_body, :status, :attempts, :last_error,
                :created_at, :sent_at
            )
            ON CONFLICT (event_id, recipient_email, email_type) DO NOTHING
            ',
            [
                'id' => $email->getId(),
                'event_id' => $email->getEventId(),
                'email_type' => $email->getEmailType(),
                'recipient_email' => $email->getRecipientEmail(),
                'recipient_name' => $email->getRecipientName(),
                'subject' => $email->getSubject(),
                'html_body' => $email->getHtmlBody(),
                'text_body' => $email->getTextBody(),
                'status' => $email->getStatus()->value,
                'attempts' => $email->getAttempts(),
                'last_error' => $email->getLastError(),
                'created_at' => $email->getCreatedAt()->format('Y-m-d H:i:s'),
                'sent_at' => $email->getSentAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function update(EmailOutbox $email): void
    {
        $this->connection->executeStatement(
            '
            UPDATE email_outbox
            SET status = :status,
                attempts = :attempts,
                last_error = :last_error,
                sent_at = :sent_at
            WHERE id = :id
            ',
            [
                'id' => $email->getId(),
                'status' => $email->getStatus()->value,
                'attempts' => $email->getAttempts(),
                'last_error' => $email->getLastError(),
                'sent_at' => $email->getSentAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function findPending(int $limit = 100): array
    {
        /** @var list<array{id: string, event_id: string, email_type: string, recipient_email: string, recipient_name: null|string, subject: string, html_body: string, text_body: null|string, status: string, attempts: int|string, last_error: null|string, created_at: string, sent_at: null|string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            '
            SELECT * FROM email_outbox
            WHERE status = :status
            ORDER BY created_at ASC
            LIMIT :limit
            ',
            [
                'status' => EmailStatus::PENDING->value,
                'limit' => $limit,
            ],
            [
                'limit' => ParameterType::INTEGER,
            ]
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findByEventId(string $eventId): array
    {
        /** @var list<array{id: string, event_id: string, email_type: string, recipient_email: string, recipient_name: null|string, subject: string, html_body: string, text_body: null|string, status: string, attempts: int|string, last_error: null|string, created_at: string, sent_at: null|string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            '
            SELECT * FROM email_outbox
            WHERE event_id = :event_id
            ORDER BY created_at ASC
            ',
            ['event_id' => $eventId]
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function countByStatus(EmailStatus $status): int
    {
        /** @var false|int|numeric-string $count */
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM email_outbox WHERE status = :status',
            ['status' => $status->value]
        );

        return (int) $count;
    }

    public function getOldestPending(): ?EmailOutbox
    {
        /** @var array{id: string, event_id: string, email_type: string, recipient_email: string, recipient_name: null|string, subject: string, html_body: string, text_body: null|string, status: string, attempts: int|string, last_error: null|string, created_at: string, sent_at: null|string}|false $row */
        $row = $this->connection->fetchAssociative(
            '
            SELECT * FROM email_outbox
            WHERE status = :status
            ORDER BY created_at ASC
            LIMIT 1
            ',
            ['status' => EmailStatus::PENDING->value]
        );

        return \is_array($row) ? $this->hydrate($row) : null;
    }

    /**
     * Hydrate row to EmailOutbox.
     *
     * @param array{
     *     id: string,
     *     event_id: string,
     *     email_type: string,
     *     recipient_email: string,
     *     recipient_name: null|string,
     *     subject: string,
     *     html_body: string,
     *     text_body: null|string,
     *     status: string,
     *     attempts: int|string,
     *     last_error: null|string,
     *     created_at: string,
     *     sent_at: null|string
     * } $row
     */
    private function hydrate(array $row): EmailOutbox
    {
        return new EmailOutbox(
            id: $row['id'],
            eventId: $row['event_id'],
            emailType: $row['email_type'],
            recipientEmail: $row['recipient_email'],
            recipientName: $row['recipient_name'],
            subject: $row['subject'],
            htmlBody: $row['html_body'],
            textBody: $row['text_body'],
            status: EmailStatus::from($row['status']),
            attempts: (int) $row['attempts'],
            lastError: $row['last_error'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            sentAt: $row['sent_at'] !== null ? new \DateTimeImmutable($row['sent_at']) : null,
        );
    }
}
