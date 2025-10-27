<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Exception;

use App\Modules\User\Domain\ValueObject\UserId;
use App\SharedKernel\Domain\Exception\DomainException;

/**
 * Exception thrown when a User aggregate is not found in the event store.
 *
 * This is a domain exception used by the write side (commands).
 *
 * Logging Philosophy:
 * - Message is STATIC: "User not found in event store"
 * - Context is DYNAMIC: {"user_id": "...", "aggregate": "User"}
 *
 * This allows grouping in Datadog/Sentry by message while keeping debug info.
 */
final class UserNotFoundException extends DomainException
{
    public static function withId(UserId $userId): self
    {
        $exception = new self('User not found in event store');  // ← STATIC message

        $exception->setContext([  // ← DYNAMIC context
            'user_id' => $userId->toString(),
            'aggregate' => 'User',
            'operation' => 'get',
        ]);

        return $exception;
    }

    public static function withIdString(string $userId): self
    {
        $exception = new self('User not found in event store');  // ← STATIC message

        $exception->setContext([  // ← DYNAMIC context
            'user_id' => $userId,
            'aggregate' => 'User',
            'operation' => 'get',
        ]);

        return $exception;
    }
}
