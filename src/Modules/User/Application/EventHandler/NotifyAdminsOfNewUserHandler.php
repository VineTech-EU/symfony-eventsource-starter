<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Application\Query\UserFinderInterface;
use App\Modules\User\Application\Service\SendAdminNotificationInterface;
use App\Modules\User\Domain\Event\UserCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Notify Admins of New User Handler.
 *
 * This handler listens to UserCreated events and notifies all admins
 * that a new user has registered and is pending approval.
 *
 * Key Points:
 * - Demonstrates multiple event handlers for the same event
 * - UserCreated event triggers BOTH welcome email AND admin notification
 * - Completely decoupled from CreateUser use case
 * - Easy to add/remove without touching domain logic
 * - Shows power of Event-Driven Architecture
 *
 * Event Flow:
 * 1. User created → UserCreated event dispatched
 * 2. SendWelcomeEmailHandler sends email to user
 * 3. UserProjectionHandler updates read model
 * 4. NotifyAdminsOfNewUserHandler (this) notifies all admins
 */
#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class NotifyAdminsOfNewUserHandler
{
    public function __construct(
        private SendAdminNotificationInterface $sendAdminNotification,
        private UserFinderInterface $userFinder,
    ) {}

    public function __invoke(UserCreated $event): void
    {
        // Find all admins in the system
        $admins = $this->userFinder->findAdmins();

        // Notify all admins about the new user pending approval
        // Pass event ID for idempotence (prevents duplicate emails on retry)
        ($this->sendAdminNotification)(
            admins: $admins,
            newUserEmail: $event->getEmail(),
            newUserName: $event->getName(),
            eventId: $event->getEventId() // Critical: enables idempotent retries
        );
    }
}
