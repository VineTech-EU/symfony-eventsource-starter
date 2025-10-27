<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Monitoring;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Doctrine\DBAL\Connection;
use Prometheus\CollectorRegistry;

/**
 * Messenger Queue Metrics Collector for Prometheus.
 *
 * Collects Symfony Messenger queue metrics:
 * - Messages in queue (pending)
 * - Failed messages
 * - Messages by queue name
 */
final readonly class MessengerMetricsCollector implements MetricsCollectorInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function init(string $namespace, CollectorRegistry $collectorRegistry): void
    {
        // No initialization needed
    }

    public function collect(CollectorRegistry $collectorRegistry): void
    {
        $this->collectQueueMetrics($collectorRegistry);
        $this->collectFailedMessagesMetrics($collectorRegistry);
    }

    private function collectQueueMetrics(CollectorRegistry $registry): void
    {
        // Messages in queue by queue name
        $queueMessagesGauge = $registry->getOrRegisterGauge(
            'symfony',
            'messenger_queue_messages',
            'Number of messages in messenger queue',
            ['queue_name', 'status']
        );

        try {
            // Available messages (not delivered)
            /** @var array<array{queue_name: string, count: int}> $availableMessages */
            $availableMessages = $this->connection->fetchAllAssociative(
                'SELECT queue_name, COUNT(*) as count
                 FROM messenger_messages
                 WHERE delivered_at IS NULL
                 GROUP BY queue_name'
            );

            foreach ($availableMessages as $row) {
                $queueMessagesGauge->set(
                    $row['count'],
                    [$row['queue_name'], 'available']
                );
            }

            // Delivered messages (processed)
            /** @var array<array{queue_name: string, count: int}> $deliveredMessages */
            $deliveredMessages = $this->connection->fetchAllAssociative(
                'SELECT queue_name, COUNT(*) as count
                 FROM messenger_messages
                 WHERE delivered_at IS NOT NULL
                 GROUP BY queue_name'
            );

            foreach ($deliveredMessages as $row) {
                $queueMessagesGauge->set(
                    $row['count'],
                    [$row['queue_name'], 'delivered']
                );
            }
        } catch (\Throwable) {
            // messenger_messages table might not exist
        }
    }

    private function collectFailedMessagesMetrics(CollectorRegistry $registry): void
    {
        // Failed messages count
        $failedMessagesGauge = $registry->getOrRegisterGauge(
            'symfony',
            'messenger_failed_messages_total',
            'Total number of failed messenger messages'
        );

        try {
            // Count messages in failure transport
            $result = $this->connection->fetchOne(
                "SELECT COUNT(*) FROM messenger_messages WHERE queue_name = 'failed'"
            );
            $failedCount = is_numeric($result) ? (int) $result : 0;

            $failedMessagesGauge->set($failedCount);
        } catch (\Throwable) {
            // Ignore if table doesn't exist
        }
    }
}
