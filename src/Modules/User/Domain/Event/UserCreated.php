<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Event;

use App\SharedKernel\Domain\DomainEvent;

/**
 * UserCreated Domain Event - V2.
 *
 * SCHEMA EVOLUTION:
 * - V1: Initial schema (email, name, roles, status)
 * - V2: Email normalization + emailVerified field (via UserCreatedV1ToV2Upcaster)
 *
 * IMPORTANT: Never modify this event's structure after deployment!
 * - Create V2→V3 upcaster if further changes needed
 * - Keep all upcasters for historical replay
 */
final class UserCreated extends DomainEvent
{
    /**
     * @param list<string> $roles User roles (e.g., ['ROLE_USER', 'ROLE_ADMIN'])
     */
    public function __construct(
        private readonly string $userId,
        private readonly string $email,
        private readonly string $name,
        private readonly array $roles,
        private readonly string $status,
    ) {
        parent::__construct();
    }

    /**
     * Current event schema version.
     *
     * Increment this when the event structure changes.
     * Upcaster chain will transform old versions to this version.
     */
    public static function getEventVersion(): int
    {
        return 2; // V2: Email normalized + emailVerified support
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public static function getEventName(): string
    {
        return 'user.created';
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
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getStatus(): string
    {
        return $this->status;
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
            'roles' => $this->roles,
            'status' => $this->status,
        ]);
    }
}
