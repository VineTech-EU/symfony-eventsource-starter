<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection;

use App\Modules\User\Application\Query\DTO\PaginatedUsersDTO;
use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;
use App\Modules\User\Application\Query\UserFinderInterface;

/**
 * User Finder - reads from UserReadModel projection.
 *
 * This implements the read side of CQRS.
 * It queries denormalized projections optimized for reads.
 * Now returns type-safe DTOs instead of arrays.
 */
final readonly class UserFinder implements UserFinderInterface
{
    public function __construct(
        private UserReadModelRepository $readModelRepository,
    ) {}

    public function findById(string $userId): ?UserDTO
    {
        $readModel = $this->readModelRepository->findById($userId);

        return $readModel !== null ? UserMapper::toDTO($readModel) : null;
    }

    public function findByEmail(string $email): ?UserDTO
    {
        $readModel = $this->readModelRepository->findByEmail($email);

        return $readModel !== null ? UserMapper::toDTO($readModel) : null;
    }

    /**
     * @return list<UserDTO>
     */
    public function findAll(): array
    {
        $readModels = $this->readModelRepository->findAll();

        return UserMapper::toDTOList($readModels);
    }

    public function findPaginated(int $page = 1, int $limit = 20): PaginatedUsersDTO
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->readModelRepository->createQueryBuilder('u');

        /** @var list<UserReadModel> $readModels */
        $readModels = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $total = $this->count();
        $pages = (int) ceil($total / $limit);

        return new PaginatedUsersDTO(
            items: UserMapper::toDTOList($readModels),
            total: $total,
            page: $page,
            limit: $limit,
            pages: $pages,
        );
    }

    /**
     * @return list<UserSummaryDTO>
     */
    public function searchByName(string $namePattern): array
    {
        $qb = $this->readModelRepository->createQueryBuilder('u');

        /** @var list<UserReadModel> $readModels */
        $readModels = $qb
            ->where('u.name LIKE :pattern')
            ->setParameter('pattern', '%' . $namePattern . '%')
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return UserMapper::toSummaryDTOList($readModels);
    }

    public function count(): int
    {
        return $this->readModelRepository->count([]);
    }

    /**
     * @return list<UserSummaryDTO>
     */
    public function findAdmins(): array
    {
        // Get all users and filter in PHP
        // This is acceptable for read models as they are denormalized for performance
        // For large datasets, consider using a native SQL query or a dedicated is_admin column
        $allReadModels = $this->readModelRepository->findAll();

        $adminReadModels = array_filter(
            $allReadModels,
            static fn (UserReadModel $user): bool => \in_array('ROLE_ADMIN', $user->getRoles(), true)
        );

        return UserMapper::toSummaryDTOList(array_values($adminReadModels));
    }
}
