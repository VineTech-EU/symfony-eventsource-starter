<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Command;

use App\Modules\User\Application\Command\DTO\CreateUserRequest;
use App\Modules\User\Application\UseCase\CreateUser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Create User Command Handler.
 *
 * This is an ADAPTER that connects Symfony Messenger to the Use Case.
 * It delegates the actual business logic to the CreateUser use case.
 *
 * Responsibilities:
 * - Receive command from message bus
 * - Create Request DTO
 * - Delegate to use case
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateUserCommandHandler
{
    public function __construct(
        private CreateUser $createUser,
    ) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $request = new CreateUserRequest(
            email: $command->email,
            name: $command->name,
        );

        $this->createUser->execute($command->userId, $request);
    }
}
