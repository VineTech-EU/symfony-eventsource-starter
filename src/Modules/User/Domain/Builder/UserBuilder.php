<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Builder;

use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;

/**
 * Domain builder for User aggregate.
 *
 * This is a pure domain pattern with NO test dependencies (no Faker).
 * Can be used in:
 * - Production code (use cases, seeders, etc.)
 * - Tests (via UserFakerFactory)
 * - Fixtures
 *
 * For test data generation, use UserFakerFactory instead.
 */
final class UserBuilder
{
    private ?string $id = null;
    private ?string $email = null;
    private ?string $name = null;
    private bool $shouldApprove = false;

    private function __construct() {}

    public static function new(): self
    {
        return new self();
    }

    public function withId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function withEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Mark user as approved (will call approve() after creation).
     */
    public function approved(): self
    {
        $this->shouldApprove = true;

        return $this;
    }

    /**
     * Keep user pending (default state).
     */
    public function pending(): self
    {
        $this->shouldApprove = false;

        return $this;
    }

    /**
     * Build the User aggregate.
     *
     * @throws \InvalidArgumentException if required fields are missing
     */
    public function build(): User
    {
        if (null === $this->id) {
            throw new \InvalidArgumentException('User ID is required. Use withId().');
        }

        if (null === $this->email) {
            throw new \InvalidArgumentException('User email is required. Use withEmail().');
        }

        if (null === $this->name) {
            throw new \InvalidArgumentException('User name is required. Use withName().');
        }

        $user = User::create(
            UserId::fromString($this->id),
            Email::fromString($this->email),
            $this->name,
        );

        if ($this->shouldApprove) {
            $user->approve();
        }

        return $user;
    }
}
