<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Modules\User\PublicApi\Event\UserEmailWasChangedIntegrationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class PublishUserEmailChangedIntegrationEvent
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.event')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(UserEmailChanged $domainEvent): void
    {
        $integrationEvent = new UserEmailWasChangedIntegrationEvent(
            userId: $domainEvent->getUserId(),
            oldEmail: $domainEvent->getOldEmail(),
            newEmail: $domainEvent->getNewEmail(),
            occurredOn: $domainEvent->getOccurredOn(),
        );

        $this->eventBus->dispatch($integrationEvent);
    }
}
