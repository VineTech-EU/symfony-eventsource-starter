<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\ValueObject;

use App\SharedKernel\Domain\ValueObject\UuidInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Symfony UUID Implementation (Adapter).
 *
 * Concrete implementation of UuidInterface using Symfony's Uuid component.
 * This keeps the Domain layer free from framework dependencies.
 *
 * The Domain layer depends on UuidInterface (abstraction),
 * not on this implementation (Dependency Inversion Principle).
 */
final readonly class SymfonyUuid implements UuidInterface
{
    private function __construct(
        private Uuid $uuid,
    ) {}

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function generate(): self
    {
        return new self(Uuid::v4());
    }

    public static function fromString(string $uuid): self
    {
        try {
            return new self(Uuid::fromString($uuid));
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid UUID string: %s', $uuid),
                0,
                $e
            );
        }
    }

    public function toString(): string
    {
        return $this->uuid->toRfc4122();
    }

    public function equals(UuidInterface $other): bool
    {
        return $this->toString() === $other->toString();
    }
}
