<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\SharedKernel\Domain\DomainEvent;

/**
 * Fake UserCreatedV2 event for testing EventTypeRegistry case sensitivity.
 *
 * @internal
 */
final class FakeUserCreatedV2 extends DomainEvent
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
        return 'test.user_created_v2';
    }
}
