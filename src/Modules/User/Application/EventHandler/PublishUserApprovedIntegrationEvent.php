<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\PublicApi\Event\UserWasApprovedIntegrationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class PublishUserApprovedIntegrationEvent
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.event')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(UserApproved $domainEvent): void
    {
        $integrationEvent = new UserWasApprovedIntegrationEvent(
            userId: $domainEvent->getUserId(),
            email: $domainEvent->getEmail(),
            name: $domainEvent->getName(),
            occurredOn: $domainEvent->getOccurredOn(),
        );

        $this->eventBus->dispatch($integrationEvent);
    }
}
