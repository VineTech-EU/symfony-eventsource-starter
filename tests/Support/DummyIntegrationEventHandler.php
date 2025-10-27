<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Modules\User\PublicApi\Event\UserEmailWasChangedIntegrationEvent;
use App\Modules\User\PublicApi\Event\UserWasApprovedIntegrationEvent;
use App\Modules\User\PublicApi\Event\UserWasCreatedIntegrationEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Dummy Integration Event Handler for tests.
 *
 * Integration events are meant to be consumed by other modules.
 * In tests, we don't have other modules yet, so we need a dummy handler
 * to prevent "No handler for message" errors.
 *
 * This handler does nothing - it just accepts the events.
 */
final class DummyIntegrationEventHandler
{
    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserWasCreated(UserWasCreatedIntegrationEvent $event): void
    {
        // Do nothing - this is just a test dummy
    }

    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserWasApproved(UserWasApprovedIntegrationEvent $event): void
    {
        // Do nothing - this is just a test dummy
    }

    #[AsMessageHandler(bus: 'messenger.bus.event')]
    public function handleUserEmailWasChanged(UserEmailWasChangedIntegrationEvent $event): void
    {
        // Do nothing - this is just a test dummy
    }
}
