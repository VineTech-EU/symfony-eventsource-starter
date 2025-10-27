<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\UserId;

/**
 * Approve User Use Case - Command Side.
 *
 * This is a WRITE operation that modifies the domain.
 * It uses UserRepositoryInterface to load aggregates from event store.
 *
 * Key points:
 * - Loads aggregate from event store (reconstitute)
 * - Executes business logic (approve())
 * - Saves new events (UserApproved)
 * - Throws exception if user not found
 *
 * Framework-agnostic and reusable.
 */
final readonly class ApproveUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(string $userId): void
    {
        $userIdVO = UserId::fromString($userId);

        // get() throws UserNotFoundException if not found
        // This is correct for commands - user MUST exist
        $user = $this->userRepository->get($userIdVO);

        // Execute domain logic - records UserApproved event
        // Will throw DomainException if user is not pending
        $user->approve();

        // Save new events to event store
        $this->userRepository->save($user);
    }
}
