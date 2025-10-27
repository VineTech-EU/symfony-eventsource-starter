<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection;

use App\Modules\User\Adapters\Persistence\Projection\ValueObject\UserStatusEnum;
use Doctrine\ORM\Mapping as ORM;

/**
 * User Read Model - Projection for queries.
 *
 * This is denormalized data optimized for reads.
 * Updated by event handlers when events are processed.
 *
 * PostgreSQL Schema: user_module
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_read_model', schema: 'user_module')]
class UserReadModel
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * @var list<string> User roles (e.g., ['ROLE_USER', 'ROLE_ADMIN'])
     */
    #[ORM\Column(type: 'json')]
    private array $roles;

    #[ORM\Column(type: 'string', length: 50, enumType: UserStatusEnum::class)]
    private UserStatusEnum $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param list<string> $roles User roles
     */
    public function __construct(
        string $id,
        string $email,
        string $name,
        array $roles,
        string $status,  // ← Still accepts string from events
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->roles = $roles;
        $this->status = UserStatusEnum::from($status);  // ← Converts to enum
        $this->createdAt = $createdAt;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeEmail(string $newEmail): void
    {
        $this->email = $newEmail;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function approve(): void
    {
        $this->status = UserStatusEnum::APPROVED;  // ← Type-safe!
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getStatus(): UserStatusEnum
    {
        return $this->status;
    }

    public function getStatusValue(): string
    {
        return $this->status->value;  // For API/JSON
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->roles, true);
    }

    public function isPending(): bool
    {
        return $this->status->isPending();  // ← Use enum methods
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return array{
     *     id: string,
     *     email: string,
     *     name: string,
     *     roles: list<string>,
     *     status: string,
     *     status_label: string,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
            'status' => $this->status->value,  // ← Convert enum to string for JSON
            'status_label' => $this->status->getLabel(),  // ← Bonus: human-readable
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
