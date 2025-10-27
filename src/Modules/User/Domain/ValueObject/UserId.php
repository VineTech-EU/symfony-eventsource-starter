<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObject;

/**
 * User ID Value Object.
 *
 * Represents a unique identifier for a User aggregate.
 * This is a pure Domain value object with no infrastructure dependencies.
 *
 * Note: UUID generation is NOT the responsibility of this Value Object.
 * Use SymfonyUuid::generate() or a UuidGenerator service in the Application
 * layer to create new IDs, then pass them to UserId::fromString().
 */
final readonly class UserId
{
    private function __construct(
        private string $value,
    ) {}

    /**
     * Create UserId from string representation.
     *
     * @param string $value UUID string (e.g., "550e8400-e29b-41d4-a716-446655440000")
     */
    public static function fromString(string $value): self
    {
        return new self($value);
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
