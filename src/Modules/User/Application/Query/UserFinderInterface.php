<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query;

use App\Modules\User\Application\Query\DTO\PaginatedUsersDTO;
use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;

/**
 * User Finder - Read Side (Projections).
 *
 * This is for QUERIES only.
 * It reads from denormalized read models (projections), NOT from event store.
 *
 * Key characteristics:
 * - Returns DTOs (type-safe, not arrays)
 * - Returns null when not found (normal for queries)
 * - Optimized for read performance
 * - Works with UserReadModel projection
 * - Can have complex search methods
 *
 * For COMMANDS that need to modify a user, use UserRepositoryInterface instead.
 */
interface UserFinderInterface
{
    /**
     * Find user by ID from read model.
     *
     * Returns null if not found (normal for queries - user might not exist).
     */
    public function findById(string $userId): ?UserDTO;

    /**
     * Find user by email from read model.
     */
    public function findByEmail(string $email): ?UserDTO;

    /**
     * Find all users from read model.
     *
     * @return list<UserDTO>
     */
    public function findAll(): array;

    /**
     * Find users with pagination.
     */
    public function findPaginated(int $page = 1, int $limit = 20): PaginatedUsersDTO;

    /**
     * Search users by name (partial match).
     *
     * @return list<UserSummaryDTO>
     */
    public function searchByName(string $namePattern): array;

    /**
     * Count total users.
     */
    public function count(): int;

    /**
     * Find all users with ROLE_ADMIN.
     *
     * @return list<UserSummaryDTO>
     */
    public function findAdmins(): array;
}
