<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query\DTO;

/**
 * User Summary DTO.
 *
 * Lightweight DTO with only essential user information.
 * Used for lists, search results, etc.
 */
final readonly class UserSummaryDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {}

    /**
     * @param array{id: string, email: string, name: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            email: $data['email'],
            name: $data['name'],
        );
    }

    /**
     * @return array{id: string, email: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
