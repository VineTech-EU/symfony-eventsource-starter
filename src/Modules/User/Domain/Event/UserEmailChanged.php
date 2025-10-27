<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Event;

use App\SharedKernel\Domain\DomainEvent;

final class UserEmailChanged extends DomainEvent
{
    public function __construct(
        private readonly string $userId,
        private readonly string $oldEmail,
        private readonly string $newEmail,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public static function getEventName(): string
    {
        return 'user.email_changed';
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getOldEmail(): string
    {
        return $this->oldEmail;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'user_id' => $this->userId,
            'old_email' => $this->oldEmail,
            'new_email' => $this->newEmail,
        ]);
    }
}
