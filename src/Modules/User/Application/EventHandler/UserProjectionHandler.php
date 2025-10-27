<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;
use App\Modules\User\Application\Query\UserReadModelRepositoryInterface;
use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Updates read model projections when User events occur.
 *
 * This keeps the query side (read model) in sync with the write side (event store).
 */
final readonly class UserProjectionHandler
{
    public function __construct(
        private UserReadModelRepositoryInterface $readModelRepository,
    ) {}

    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserCreated(UserCreated $event): void
    {
        $readModel = new UserReadModel(
            id: $event->getUserId(),
            email: $event->getEmail(),
            name: $event->getName(),
            roles: $event->getRoles(),
            status: $event->getStatus(),
            createdAt: $event->getOccurredOn(),
        );

        $this->readModelRepository->save($readModel);
    }

    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserEmailChanged(UserEmailChanged $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getUserId());

        if (null === $readModel) {
            throw new \RuntimeException(\sprintf('User read model not found: %s', $event->getUserId()));
        }

        $readModel->changeEmail($event->getNewEmail());

        $this->readModelRepository->save($readModel);
    }

    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserApproved(UserApproved $event): void
    {
        $readModel = $this->readModelRepository->findById($event->getUserId());

        if (null === $readModel) {
            throw new \RuntimeException(\sprintf('User read model not found: %s', $event->getUserId()));
        }

        $readModel->approve();

        $this->readModelRepository->save($readModel);
    }
}
