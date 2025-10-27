<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Domain Context Processor for Monolog.
 *
 * Enriches EVERY log entry with domain-specific context:
 * - Application name
 * - Environment
 * - Microservice name (for distributed systems)
 * - Any custom tags
 *
 * This processor is called automatically by Monolog for every log.
 * Perfect for Datadog/Sentry/ELK filtering and grouping.
 */
final readonly class DomainContextProcessor implements ProcessorInterface
{
    public function __construct(
        private string $appName = 'symfony-eventsource',
        private string $environment = 'dev',
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        // Add global context to EVERY log
        $record->extra['app'] = $this->appName;
        $record->extra['env'] = $this->environment;
        $record->extra['microservice'] = 'user-service';  // Changeable per contexte

        // Add timestamp in multiple formats for different tools
        $record->extra['timestamp_iso'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        // Add memory usage (useful for performance debugging)
        $record->extra['memory_usage_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);

        return $record;
    }
}
