<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\SharedKernel\Domain\DomainEvent;

/**
 * Fake DuplicateUserCreated event for testing EventTypeRegistry duplicate detection.
 *
 * @internal
 */
final class FakeDuplicateUserCreated extends DomainEvent
{
    public function __construct(
        private readonly string $aggregateId,
    ) {
        parent::__construct();
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public static function getEventName(): string
    {
        return 'test.duplicate_user_created';
    }
}
