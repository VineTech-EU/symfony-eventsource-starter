<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Command\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Change User Email Request DTO.
 *
 * Represents a validated request to change a user's email.
 */
final readonly class ChangeUserEmailRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'User ID is required')]
        #[Assert\Uuid(message: 'User ID must be a valid UUID')]
        public string $userId,
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email format')]
        #[Assert\Length(max: 255, maxMessage: 'Email must not exceed {{ limit }} characters')]
        public string $newEmail,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $userId, array $data): self
    {
        $newEmail = $data['email'] ?? '';

        return new self(
            userId: $userId,
            newEmail: \is_string($newEmail) ? $newEmail : '',
        );
    }
}
