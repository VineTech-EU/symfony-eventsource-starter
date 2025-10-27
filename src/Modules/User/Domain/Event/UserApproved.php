<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Event;

use App\SharedKernel\Domain\DomainEvent;

/**
 * User Approved Event.
 *
 * Emitted when an admin approves a pending user.
 */
final class UserApproved extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly string $name,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public static function getEventName(): string
    {
        return 'user.approved';
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user_id' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
        ]);
    }
}
