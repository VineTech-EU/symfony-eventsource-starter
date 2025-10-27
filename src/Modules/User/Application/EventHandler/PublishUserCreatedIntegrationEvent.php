<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\PublicApi\Event\UserWasCreatedIntegrationEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Translates Domain Event → Integration Event.
 *
 * Philosophy:
 * - Domain events are internal to the User module
 * - Integration events are the PUBLIC API for other modules
 * - This handler acts as a translator/adapter
 */
#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class PublishUserCreatedIntegrationEvent
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.event')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(UserCreated $domainEvent): void
    {
        // Translate domain event to integration event
        // Note: Integration events simplify to a single primary role
        $roles = $domainEvent->getRoles();
        $integrationEvent = new UserWasCreatedIntegrationEvent(
            userId: $domainEvent->getUserId(),
            email: $domainEvent->getEmail(),
            name: $domainEvent->getName(),
            role: $roles[0] ?? 'ROLE_USER', // Primary role
            status: $domainEvent->getStatus(),
            occurredOn: $domainEvent->getOccurredOn(),
        );

        // Dispatch to other modules
        $this->eventBus->dispatch($integrationEvent);
    }
}
