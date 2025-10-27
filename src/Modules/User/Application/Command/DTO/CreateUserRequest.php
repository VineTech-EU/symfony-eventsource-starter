<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Command\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Create User Request DTO.
 *
 * Represents a validated request to create a new user.
 * Contains all necessary validation rules.
 *
 * Benefits:
 * - Type-safe
 * - Self-validating (Symfony Validator constraints)
 * - Clear separation of concerns
 * - Reusable across different entry points (HTTP, CLI, tests)
 */
final readonly class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 255, maxMessage: 'Email must not exceed {{ limit }} characters')]
        public string $email,
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(
            min: 2,
            max: 100,
            minMessage: 'Name must be at least {{ limit }} characters',
            maxMessage: 'Name must not exceed {{ limit }} characters'
        )]
        public string $name,
    ) {}

    /**
     * Create from array (useful for controllers).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';

        return new self(
            email: \is_string($email) ? $email : '',
            name: \is_string($name) ? $name : '',
        );
    }
}
