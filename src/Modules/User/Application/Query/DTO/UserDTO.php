<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query\DTO;

/**
 * User Read DTO.
 *
 * This DTO represents a user from the read model (projection).
 * It's used for queries and provides a type-safe alternative to arrays.
 *
 * Benefits:
 * - Type-safe (no need for PHPDoc array shapes)
 * - IDE autocompletion
 * - Easy to refactor
 * - Immutable (readonly)
 * - Clear contract for API responses
 */
final readonly class UserDTO
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public array $roles,
        public string $status,
        public string $statusLabel,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * Create from array (for backward compatibility or testing).
     *
     * @param array{id: string, email: string, name: string, roles: list<string>, status: string, status_label: string, created_at: string, updated_at: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            email: $data['email'],
            name: $data['name'],
            roles: $data['roles'],
            status: $data['status'],
            statusLabel: $data['status_label'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at'],
        );
    }

    /**
     * Convert to array (for JSON serialization).
     *
     * @return array{id: string, email: string, name: string, roles: list<string>, status: string, status_label: string, created_at: string, updated_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
