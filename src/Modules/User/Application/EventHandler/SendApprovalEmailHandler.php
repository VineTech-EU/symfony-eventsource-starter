<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Application\Service\SendApprovalConfirmationEmailInterface;
use App\Modules\User\Domain\Event\UserApproved;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Send Approval Email Handler.
 *
 * This handler listens to UserApproved events and sends confirmation email.
 *
 * ✅ EXAMPLE: Handler that uses NEITHER Finder NOR Repository
 *
 * Key Points:
 * - Only needs data from the event itself (email, name)
 * - No need to query database (Finder)
 * - No need to modify aggregates (Repository)
 * - Simplest type of event handler
 * - Just a side effect (send email)
 *
 * This is the MOST COMMON pattern for notification handlers.
 */
#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class SendApprovalEmailHandler
{
    public function __construct(
        private SendApprovalConfirmationEmailInterface $sendApprovalEmail,
    ) {}

    public function __invoke(UserApproved $event): void
    {
        // ✅ All data needed is in the event
        // ❌ No Finder needed
        // ❌ No Repository needed

        ($this->sendApprovalEmail)(
            recipientEmail: $event->getEmail(),
            recipientName: $event->getName(),
            eventId: $event->getEventId()
        );
    }
}
