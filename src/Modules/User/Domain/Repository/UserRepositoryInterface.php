<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Repository;

use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Exception\UserNotFoundException;
use App\Modules\User\Domain\ValueObject\UserId;

/**
 * User Repository - Write Side (Event Sourcing).
 *
 * This repository is for COMMANDS only.
 * It works with the event store to save/load aggregates.
 *
 * Key differences from traditional repositories:
 * - No find methods (use UserFinderInterface for queries)
 * - get() throws exception if not found (commands expect the entity)
 * - Saves events, not state
 * - Reconstructs aggregates from event stream
 */
interface UserRepositoryInterface
{
    /**
     * Save user by appending events to event store.
     *
     * This appends new events to the event stream and dispatches them
     * to the event bus for projection updates.
     */
    public function save(User $user): void;

    /**
     * Get user by ID (reconstitute from event store).
     *
     * Use this in COMMANDS when you MUST have the user.
     * If the user doesn't exist, it's an error in the command flow.
     *
     * For QUERIES, use UserFinderInterface instead.
     *
     * @throws UserNotFoundException if user not found in event store
     */
    public function get(UserId $userId): User;
}
