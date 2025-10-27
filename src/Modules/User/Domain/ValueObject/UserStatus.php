<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObject;

/**
 * User Status Value Object.
 *
 * Represents the approval status of a user.
 */
final class UserStatus
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    private const VALID_STATUSES = [
        self::PENDING,
        self::APPROVED,
        self::REJECTED,
    ];

    private function __construct(
        private readonly string $value,
    ) {}

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function approved(): self
    {
        return new self(self::APPROVED);
    }

    public static function rejected(): self
    {
        return new self(self::REJECTED);
    }

    public static function fromString(string $status): self
    {
        if (!\in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid user status "%s". Valid statuses are: %s', $status, implode(', ', self::VALID_STATUSES)));
        }

        return new self($status);
    }

    public function isPending(): bool
    {
        return self::PENDING === $this->value;
    }

    public function isApproved(): bool
    {
        return self::APPROVED === $this->value;
    }

    public function isRejected(): bool
    {
        return self::REJECTED === $this->value;
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
