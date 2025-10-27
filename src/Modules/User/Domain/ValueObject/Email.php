<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObject;

final readonly class Email
{
    private function __construct(
        private string $value,
    ) {
        if (false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(\sprintf('Invalid email: %s', $value));
        }
    }

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
