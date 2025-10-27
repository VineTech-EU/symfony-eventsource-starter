<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection\ValueObject;

/**
 * User Role Enum - Projection Layer.
 *
 * Separate enum from Domain's UserRole.
 * Specific to the read side for queries and display.
 */
enum UserRoleEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * Check if role is admin.
     */
    public function isAdmin(): bool
    {
        return self::ROLE_ADMIN === $this;
    }

    /**
     * Check if role is user.
     */
    public function isUser(): bool
    {
        return self::ROLE_USER === $this;
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ROLE_USER => 'User',
            self::ROLE_ADMIN => 'Administrator',
        };
    }

    /**
     * Get all possible roles.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
}
