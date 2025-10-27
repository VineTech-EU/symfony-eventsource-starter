<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Application\Command\DTO\CreateUserRequest;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;

/**
 * Create User Use Case.
 *
 * This is the core business logic for creating a user.
 * It's framework-agnostic and can be called from:
 * - Command Handlers (async via Messenger)
 * - Controllers (direct HTTP)
 * - CLI Commands
 * - Event Handlers
 * - Tests
 *
 * Now accepts a validated CreateUserRequest DTO for type-safety.
 */
final readonly class CreateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function execute(string $userId, CreateUserRequest $request): void
    {
        $userIdVO = UserId::fromString($userId);
        $emailVO = Email::fromString($request->email);

        // Create aggregate - records UserCreated event
        $user = User::create($userIdVO, $emailVO, $request->name);

        // Save to event store and dispatch events
        $this->userRepository->save($user);
    }
}
