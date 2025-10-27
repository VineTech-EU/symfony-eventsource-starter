<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query;

use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\UseCase\GetUser;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Get User Query Handler.
 *
 * This is an ADAPTER that connects Symfony Messenger to the Use Case.
 * It delegates the actual query logic to the GetUser use case.
 *
 * Responsibilities:
 * - Receive query from message bus
 * - Delegate to use case
 * - Return type-safe DTO result
 */
#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class GetUserQueryHandler
{
    public function __construct(
        private GetUser $getUser,
    ) {}

    public function __invoke(GetUserQuery $query): ?UserDTO
    {
        return $this->getUser->execute(userId: $query->userId);
    }
}
