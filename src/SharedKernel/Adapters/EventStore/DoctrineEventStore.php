<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Domain\DomainEvent;
use App\SharedKernel\Domain\EventStore\EventStoreException;
use App\SharedKernel\Domain\EventStore\EventStoreInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineEventStore implements EventStoreInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventSerializer $eventSerializer,
    ) {}

    public function append(string $aggregateId, array $events, int $expectedVersion): void
    {
        if ([] === $events) {
            return;
        }

        // Get current version
        $currentVersion = $this->getCurrentVersion($aggregateId);

        // Check optimistic concurrency
        if ($currentVersion !== $expectedVersion) {
            throw EventStoreException::concurrencyConflict($aggregateId, $expectedVersion, $currentVersion);
        }

        $version = $currentVersion;

        try {
            foreach ($events as $event) {
                ++$version;

                $eventStoreEntity = new EventStoreEntity(
                    eventId: $event->getEventId(),
                    aggregateId: $aggregateId,
                    aggregateType: $this->getAggregateTypeFromEvent($event),
                    eventType: $event::getEventName(), // Store stable event name (e.g., "user.created")
                    payload: $this->eventSerializer->serialize($event),
                    version: $version,
                    occurredOn: $event->getOccurredOn(),
                    metadata: $event->getMetadata(),
                    eventVersion: $event::getEventVersion(),
                );

                $this->entityManager->persist($eventStoreEntity);
            }

            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw EventStoreException::concurrencyConflict($aggregateId, $expectedVersion, $currentVersion);
        }
    }

    public function getEventsForAggregate(string $aggregateId): array
    {
        return $this->getEventsForAggregateFromVersion($aggregateId, 0);
    }

    public function getEventsForAggregateFromVersion(string $aggregateId, int $fromVersion): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $eventEntities = $qb
            ->select('e')
            ->from(EventStoreEntity::class, 'e')
            ->where('e.aggregateId = :aggregateId')
            ->andWhere('e.version > :fromVersion')
            ->orderBy('e.version', 'ASC')
            ->setParameter('aggregateId', $aggregateId)
            ->setParameter('fromVersion', $fromVersion)
            ->getQuery()
            ->getResult()
        ;

        return array_map(
            fn (EventStoreEntity $entity) => $this->eventSerializer->deserialize(
                $entity->getEventType(),
                $entity->getPayload(),
                $entity->getEventVersion()
            ),
            $eventEntities
        );
    }

    private function getCurrentVersion(string $aggregateId): int
    {
        $qb = $this->entityManager->createQueryBuilder();

        $result = $qb
            ->select('MAX(e.version)')
            ->from(EventStoreEntity::class, 'e')
            ->where('e.aggregateId = :aggregateId')
            ->setParameter('aggregateId', $aggregateId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    private function getAggregateTypeFromEvent(DomainEvent $event): string
    {
        // Extract aggregate type from event namespace
        $reflection = new \ReflectionClass($event);
        $namespace = $reflection->getNamespaceName();

        // Example: App\Modules\User\Domain\Event -> User
        if (1 === preg_match('/Modules\\\(\w+)\\\Domain\\\Event/', $namespace, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }
}
