<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query\DTO;

/**
 * Paginated Users DTO.
 *
 * Represents a paginated list of users with metadata.
 */
final readonly class PaginatedUsersDTO
{
    /**
     * @param list<UserDTO> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
        public int $pages,
    ) {}

    /**
     * @return array{items: list<array{id: string, email: string, name: string, roles: list<string>, status: string, status_label: string, created_at: string, updated_at: string}>, total: int, page: int, limit: int, pages: int}
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (UserDTO $user) => $user->toArray(), $this->items),
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
            'pages' => $this->pages,
        ];
    }
}
