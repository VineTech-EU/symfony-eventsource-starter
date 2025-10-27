<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\ValueObject;

/**
 * UUID Interface (Domain Abstraction).
 *
 * Defines the contract for UUID value objects across all modules.
 * This abstraction prevents the Domain layer from depending on
 * specific UUID implementations (Symfony, Ramsey, etc.).
 *
 * Benefits:
 * - Domain layer remains framework-agnostic
 * - Can swap UUID implementations without affecting domain
 * - Easy to mock in tests
 * - Prevents vendor lock-in
 */
interface UuidInterface extends \Stringable
{
    /**
     * Create a new random UUID (v4).
     */
    public static function generate(): self;

    /**
     * Create UUID from string representation.
     *
     * @throws \InvalidArgumentException if string is not a valid UUID
     */
    public static function fromString(string $uuid): self;

    /**
     * Get string representation of UUID.
     */
    public function toString(): string;

    /**
     * Compare with another UUID for equality.
     */
    public function equals(self $other): bool;
}
