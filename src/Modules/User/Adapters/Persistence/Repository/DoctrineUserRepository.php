<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Repository;

use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Exception\UserNotFoundException;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\UserId;
use App\SharedKernel\Application\Bus\EventBusInterface;
use App\SharedKernel\Domain\EventStore\EventStoreInterface;

/**
 * Event Sourced User Repository.
 *
 * This repository:
 * 1. Saves events to the event store (not the aggregate state)
 * 2. Reconstructs aggregates from the event stream
 * 3. Dispatches events to the event bus for projections
 */
final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EventStoreInterface $eventStore,
        private EventBusInterface $eventBus,
    ) {}

    public function save(User $user): void
    {
        $events = $user->pullDomainEvents();

        if ([] === $events) {
            return; // No changes
        }

        // Calculate expected version (current version - number of new events)
        $expectedVersion = $user->getVersion() - \count($events);

        // Append events to event store with optimistic concurrency check
        $this->eventStore->append(
            aggregateId: $user->getId(),
            events: $events,
            expectedVersion: $expectedVersion
        );

        // Dispatch events for projections and other handlers
        $this->eventBus->dispatchAll($events);
    }

    public function get(UserId $userId): User
    {
        $events = $this->eventStore->getEventsForAggregate($userId->toString());

        if ([] === $events) {
            throw UserNotFoundException::withId($userId);
        }

        // Reconstitute aggregate from event stream
        return User::reconstitute($events);
    }
}
