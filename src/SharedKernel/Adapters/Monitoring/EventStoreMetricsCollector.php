<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Monitoring;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Doctrine\DBAL\Connection;
use Prometheus\CollectorRegistry;

/**
 * Event Store Metrics Collector for Prometheus.
 *
 * Collects Event Sourcing specific metrics:
 * - Total events count
 * - Events by type
 * - Events by module (user, order, billing, inventory, notification)
 * - Projection counts
 * - Projection lag
 */
final readonly class EventStoreMetricsCollector implements MetricsCollectorInterface
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
        $this->collectEventStoreMetrics($collectorRegistry);
        $this->collectProjectionMetrics($collectorRegistry);
    }

    private function collectEventStoreMetrics(CollectorRegistry $registry): void
    {
        // Total events in event store
        $totalEventsGauge = $registry->getOrRegisterGauge(
            'symfony',
            'event_store_events_total',
            'Total number of events in event store'
        );

        $result = $this->connection->fetchOne('SELECT COUNT(*) FROM event_store');
        $totalEvents = is_numeric($result) ? (int) $result : 0;
        $totalEventsGauge->set($totalEvents);

        // Events by type
        $eventsByTypeGauge = $registry->getOrRegisterGauge(
            'symfony',
            'event_store_events_by_type',
            'Events count grouped by event type',
            ['event_type', 'module']
        );

        /** @var array<array{event_type: string, count: int}> $eventsByType */
        $eventsByType = $this->connection->fetchAllAssociative(
            'SELECT event_type, COUNT(*) as count FROM event_store GROUP BY event_type'
        );

        foreach ($eventsByType as $row) {
            $eventType = $row['event_type'];
            $count = $row['count'];

            // Extract module from event_type (e.g., "user.created" -> "user")
            $module = 'unknown';
            if (str_contains($eventType, '.')) {
                $module = explode('.', $eventType, 2)[0];
            }

            $eventsByTypeGauge->set($count, [$eventType, $module]);
        }

        // Events by aggregate type
        $eventsByAggregateGauge = $registry->getOrRegisterGauge(
            'symfony',
            'event_store_events_by_aggregate',
            'Events count grouped by aggregate type',
            ['aggregate_type']
        );

        /** @var array<array{aggregate_type: string, count: int}> $eventsByAggregate */
        $eventsByAggregate = $this->connection->fetchAllAssociative(
            'SELECT aggregate_type, COUNT(*) as count FROM event_store GROUP BY aggregate_type'
        );

        foreach ($eventsByAggregate as $row) {
            $eventsByAggregateGauge->set(
                $row['count'],
                [$row['aggregate_type']]
            );
        }
    }

    private function collectProjectionMetrics(CollectorRegistry $registry): void
    {
        // User projection count
        $projectionCountGauge = $registry->getOrRegisterGauge(
            'symfony',
            'projection_count',
            'Number of records in projection',
            ['projection', 'module']
        );

        try {
            $result = $this->connection->fetchOne('SELECT COUNT(*) FROM user_module.user_read_model');
            $userCount = is_numeric($result) ? (int) $result : 0;
            $projectionCountGauge->set($userCount, ['user_read_model', 'user']);
        } catch (\Throwable) {
            // Table might not exist yet
        }

        // Projection lag (latest event timestamp - latest projection update)
        $projectionLagGauge = $registry->getOrRegisterGauge(
            'symfony',
            'projection_lag_seconds',
            'Projection lag in seconds (how far behind the event store)',
            ['projection', 'module']
        );

        try {
            // Get latest event timestamp
            $latestEventTime = $this->connection->fetchOne(
                'SELECT recorded_on FROM event_store ORDER BY recorded_on DESC LIMIT 1'
            );

            // Get latest projection update
            $latestProjectionUpdate = $this->connection->fetchOne(
                'SELECT updated_at FROM user_module.user_read_model ORDER BY updated_at DESC LIMIT 1'
            );

            if (\is_string($latestEventTime) && \is_string($latestProjectionUpdate)) {
                $eventTimestamp = new \DateTimeImmutable($latestEventTime);
                $projectionTimestamp = new \DateTimeImmutable($latestProjectionUpdate);

                $lagSeconds = $eventTimestamp->getTimestamp() - $projectionTimestamp->getTimestamp();
                $projectionLagGauge->set(max(0, $lagSeconds), ['user_read_model', 'user']);
            }
        } catch (\Throwable) {
            // Ignore errors (projections might be empty)
        }
    }
}
