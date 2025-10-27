<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query;

use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;

/**
 * User Read Model Repository Interface (Application Port).
 *
 * Defines the contract for persisting and querying User projections.
 * This is an Application-layer port for the CQRS read side.
 *
 * Implementation: UserReadModelRepository (Adapters layer)
 *
 * Benefits:
 * - Dependency Inversion Principle (DIP) compliance
 * - Application layer doesn't depend on Adapters
 * - Easy to mock in tests
 * - Can swap implementations (e.g., different storage backends)
 */
interface UserReadModelRepositoryInterface
{
    /**
     * Persist a user read model.
     */
    public function save(UserReadModel $readModel): void;

    /**
     * Find a user read model by ID.
     */
    public function findById(string $id): ?UserReadModel;

    /**
     * Find a user read model by email.
     */
    public function findByEmail(string $email): ?UserReadModel;

    /**
     * Delete a user read model.
     */
    public function delete(UserReadModel $readModel): void;

    /**
     * Find all user read models.
     *
     * @return list<UserReadModel>
     */
    public function findAll(): array;
}
