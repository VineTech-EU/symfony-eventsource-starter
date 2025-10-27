<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\EventStore;

class EventStoreException extends \RuntimeException
{
    public static function concurrencyConflict(string $aggregateId, int $expected, int $actual): self
    {
        return new self(
            \sprintf(
                'Concurrency conflict for aggregate %s. Expected version %d, but found %d',
                $aggregateId,
                $expected,
                $actual
            )
        );
    }

    public static function aggregateNotFound(string $aggregateId): self
    {
        return new self(\sprintf('Aggregate %s not found in event store', $aggregateId));
    }
}
