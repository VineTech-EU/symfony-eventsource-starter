<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\UserFinderInterface;

/**
 * Get User Use Case - Read Side.
 *
 * This use case is for QUERIES.
 * It uses UserFinderInterface to read from projections (read models).
 *
 * Key points:
 * - NEVER loads from event store
 * - NEVER loads aggregates
 * - Reads from denormalized projections
 * - Returns type-safe DTOs (not arrays)
 * - Returns null if not found (normal for queries)
 *
 * Framework-agnostic and reusable.
 */
final readonly class GetUser
{
    public function __construct(
        private UserFinderInterface $userFinder,
    ) {}

    public function execute(string $userId): ?UserDTO
    {
        return $this->userFinder->findById($userId);
    }
}
