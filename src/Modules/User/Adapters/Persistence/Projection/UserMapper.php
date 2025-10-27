<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection;

use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;

/**
 * User Mapper.
 *
 * Maps UserReadModel to DTOs.
 * This keeps the mapping logic in the infrastructure layer.
 */
final class UserMapper
{
    public static function toDTO(UserReadModel $model): UserDTO
    {
        return new UserDTO(
            id: $model->getId(),
            email: $model->getEmail(),
            name: $model->getName(),
            roles: $model->getRoles(),
            status: $model->getStatus()->value,
            statusLabel: $model->getStatus()->getLabel(),
            createdAt: $model->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $model->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public static function toSummaryDTO(UserReadModel $model): UserSummaryDTO
    {
        return new UserSummaryDTO(
            id: $model->getId(),
            email: $model->getEmail(),
            name: $model->getName(),
        );
    }

    /**
     * @param list<UserReadModel> $models
     *
     * @return list<UserDTO>
     */
    public static function toDTOList(array $models): array
    {
        return array_map(
            static fn (UserReadModel $model) => self::toDTO($model),
            $models
        );
    }

    /**
     * @param list<UserReadModel> $models
     *
     * @return list<UserSummaryDTO>
     */
    public static function toSummaryDTOList(array $models): array
    {
        return array_map(
            static fn (UserReadModel $model) => self::toSummaryDTO($model),
            $models
        );
    }
}
