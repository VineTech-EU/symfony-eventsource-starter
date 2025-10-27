<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Command;

use App\Modules\User\Application\Command\DTO\ChangeUserEmailRequest;
use App\Modules\User\Application\UseCase\ChangeUserEmail;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Change User Email Command Handler.
 *
 * This is an ADAPTER that connects Symfony Messenger to the Use Case.
 * It delegates the actual business logic to the ChangeUserEmail use case.
 *
 * Responsibilities:
 * - Receive command from message bus
 * - Create Request DTO
 * - Delegate to use case
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ChangeUserEmailCommandHandler
{
    public function __construct(
        private ChangeUserEmail $changeUserEmail,
    ) {}

    public function __invoke(ChangeUserEmailCommand $command): void
    {
        $request = new ChangeUserEmailRequest(
            userId: $command->userId,
            newEmail: $command->newEmail,
        );

        $this->changeUserEmail->execute($request);
    }
}
