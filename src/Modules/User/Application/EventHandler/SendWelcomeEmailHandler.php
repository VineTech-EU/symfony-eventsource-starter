<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Application\Service\SendWelcomeEmailInterface;
use App\Modules\User\Domain\Event\UserCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Send Welcome Email Handler.
 *
 * This handler listens to UserCreated events and sends a welcome email.
 *
 * Key Points:
 * - Completely decoupled from the CreateUser use case
 * - The use case doesn't know about email sending
 * - Easy to add/remove without touching domain logic
 * - Demonstrates Event-Driven Architecture benefits
 * - Can be async via RabbitMQ for better performance
 * - Uses dedicated SendWelcomeEmail service (one service per email type)
 *
 * This is a perfect example of the Single Responsibility Principle
 * and the power of Event Sourcing.
 */
#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private SendWelcomeEmailInterface $sendWelcomeEmail,
    ) {}

    public function __invoke(UserCreated $event): void
    {
        // Create welcome email in outbox (sent asynchronously by consumer)
        ($this->sendWelcomeEmail)(
            recipientEmail: $event->getEmail(),
            recipientName: $event->getName(),
            eventId: $event->getEventId()
        );
    }
}
