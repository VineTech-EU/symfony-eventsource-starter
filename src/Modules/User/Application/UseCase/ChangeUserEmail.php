<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Application\Command\DTO\ChangeUserEmailRequest;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;

/**
 * Change User Email Use Case.
 *
 * Demonstrates Event Sourcing reconstruction:
 * 1. Load aggregate from event store (reconstitute)
 * 2. Execute business logic
 * 3. Save new events
 *
 * This use case is framework-agnostic and reusable.
 * Now accepts a validated ChangeUserEmailRequest DTO for type-safety.
 */
final readonly class ChangeUserEmail
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(ChangeUserEmailRequest $request): void
    {
        $userIdVO = UserId::fromString($request->userId);

        // get() throws UserNotFoundException if not found
        // This is correct for commands - user MUST exist
        $user = $this->userRepository->get($userIdVO);

        $newEmailVO = Email::fromString($request->newEmail);

        // Execute domain logic - records UserEmailChanged event
        $user->changeEmail($newEmailVO);

        // Save new events to event store
        $this->userRepository->save($user);
    }
}
