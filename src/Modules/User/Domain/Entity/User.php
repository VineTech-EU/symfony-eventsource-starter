<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Entity;

use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;
use App\Modules\User\Domain\ValueObject\UserRole;
use App\Modules\User\Domain\ValueObject\UserStatus;
use App\SharedKernel\Domain\AggregateRoot;
use App\SharedKernel\Domain\DomainEvent;

/**
 * User Aggregate Root - Event Sourced.
 *
 * This entity is NOT persisted in a traditional table.
 * Its state is reconstructed from events in the event_store.
 */
class User extends AggregateRoot
{
    private UserId $id;
    private Email $email;
    private string $name;

    /** @var UserRole[] */
    private array $roles;
    private UserStatus $status;
    private \DateTimeImmutable $createdAt;

    /**
     * Protected constructor - use named constructors or reconstitute().
     * Protected (not private) to allow AggregateRoot::reconstitute() to work.
     */
    protected function __construct() {}

    /**
     * Create a new user (command).
     * New users are created with ROLE_USER and pending status by default.
     */
    public static function create(
        UserId $id,
        Email $email,
        string $name,
    ): self {
        $user = new self();

        // New users have ROLE_USER and pending status
        $roles = [UserRole::user()];
        $status = UserStatus::pending();

        // Record event - this will also apply it
        $user->recordEvent(new UserCreated(
            $id->toString(),
            $email->toString(),
            $name,
            array_map(static fn (UserRole $role) => $role->toString(), $roles),
            $status->toString(),
        ));

        return $user;
    }

    /**
     * Change user email (command).
     */
    public function changeEmail(Email $newEmail): void
    {
        if ($this->email->equals($newEmail)) {
            throw new \DomainException('Cannot change to the same email address');
        }

        $this->recordEvent(new UserEmailChanged(
            $this->id->toString(),
            $this->email->toString(),
            $newEmail->toString(),
        ));
    }

    /**
     * Approve user (command).
     * Only pending users can be approved.
     */
    public function approve(): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException(\sprintf('Cannot approve user %s: user is not pending (current status: %s)', $this->id->toString(), $this->status->toString()));
        }

        $this->recordEvent(new UserApproved(
            $this->id->toString(),
            $this->email->toString(),
            $this->name,
        ));
    }

    // Getters

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getUserId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return UserRole[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    /**
     * Apply events to rebuild state (Event Sourcing).
     */
    protected function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof UserCreated => $this->applyUserCreated($event),
            $event instanceof UserEmailChanged => $this->applyUserEmailChanged($event),
            $event instanceof UserApproved => $this->applyUserApproved($event),
            default => throw new \RuntimeException(\sprintf('Unknown event type: %s', $event::class)),
        };
    }

    private function applyUserCreated(UserCreated $event): void
    {
        $this->id = UserId::fromString($event->getUserId());
        $this->email = Email::fromString($event->getEmail());
        $this->name = $event->getName();
        $this->roles = array_map(
            static fn (string $role) => UserRole::fromString($role),
            $event->getRoles()
        );
        $this->status = UserStatus::fromString($event->getStatus());
        $this->createdAt = $event->getOccurredOn();
    }

    private function applyUserEmailChanged(UserEmailChanged $event): void
    {
        $this->email = Email::fromString($event->getNewEmail());
    }

    private function applyUserApproved(UserApproved $event): void
    {
        $this->status = UserStatus::approved();
    }
}
