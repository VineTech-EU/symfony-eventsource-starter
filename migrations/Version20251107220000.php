<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create email_outbox table for Transactional Outbox Pattern.
 *
 * This table stores all emails to be sent, providing:
 * - Transactional guarantees (email creation in same transaction as event)
 * - Idempotence (no duplicate emails)
 * - Monitoring (see all pending/sent/failed emails)
 * - Retry mechanism (consumer retries failed emails)
 * - Audit trail (complete history)
 */
final class Version20251107220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create email_outbox table for Transactional Outbox Pattern';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE email_outbox (
                id UUID PRIMARY KEY,
                event_id UUID NOT NULL,
                email_type VARCHAR(50) NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                recipient_name VARCHAR(255),
                subject TEXT NOT NULL,
                html_body TEXT NOT NULL,
                text_body TEXT,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                attempts INT NOT NULL DEFAULT 0,
                last_error TEXT,
                created_at TIMESTAMP NOT NULL,
                sent_at TIMESTAMP,
                CONSTRAINT check_status CHECK (status IN ('pending', 'sent', 'failed'))
            )
        ");

        // Index for finding pending emails (consumer query)
        $this->addSql('CREATE INDEX idx_email_outbox_status_created ON email_outbox (status, created_at)');

        // Index for idempotence checks (prevent duplicates)
        $this->addSql('CREATE UNIQUE INDEX idx_email_outbox_event_recipient_type ON email_outbox (event_id, recipient_email, email_type)');

        // Index for monitoring queries
        $this->addSql('CREATE INDEX idx_email_outbox_event_id ON email_outbox (event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS email_outbox');
    }
}
