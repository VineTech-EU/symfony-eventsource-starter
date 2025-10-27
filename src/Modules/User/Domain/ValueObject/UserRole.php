<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObject;

/**
 * User Role Value Object.
 *
 * Represents user roles in the system.
 */
final class UserRole
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    private const VALID_ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
    ];

    private function __construct(
        private readonly string $value,
    ) {}

    public static function user(): self
    {
        return new self(self::ROLE_USER);
    }

    public static function admin(): self
    {
        return new self(self::ROLE_ADMIN);
    }

    public static function fromString(string $role): self
    {
        if (!\in_array($role, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid user role "%s". Valid roles are: %s', $role, implode(', ', self::VALID_ROLES)));
        }

        return new self($role);
    }

    public function isAdmin(): bool
    {
        return self::ROLE_ADMIN === $this->value;
    }

    public function isUser(): bool
    {
        return self::ROLE_USER === $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
