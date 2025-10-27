<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Monitoring;

use Doctrine\DBAL\Connection;
use Laminas\Diagnostics\Check\AbstractCheck;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;

/**
 * Event Store Health Check.
 *
 * Verifies:
 * - Event store table exists
 * - Event store is accessible
 * - Contains events (warning if empty)
 */
final class EventStoreHealthCheck extends AbstractCheck
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function check(): Failure|Success|Warning
    {
        try {
            // Check if event_store table exists
            $schemaManager = $this->connection->createSchemaManager();
            $tables = $schemaManager->listTableNames();

            if (!\in_array('event_store', $tables, true)) {
                return new Failure('Event store table does not exist');
            }

            // Check if event store is accessible and has events
            $result = $this->connection->fetchOne('SELECT COUNT(*) FROM event_store');
            $eventCount = is_numeric($result) ? (int) $result : 0;

            if (0 === $eventCount) {
                return new Warning('Event store is empty (no events recorded yet)');
            }

            return new Success(\sprintf('Event store is healthy (%d events)', $eventCount));
        } catch (\Throwable $e) {
            return new Failure('Event store check failed: ' . $e->getMessage());
        }
    }

    public function getLabel(): string
    {
        return 'Event Store Health Check';
    }
}
