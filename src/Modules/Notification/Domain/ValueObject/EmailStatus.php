<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\ValueObject;

/**
 * Email Status.
 *
 * Represents the lifecycle of an email in the outbox:
 * - pending: Email created, waiting to be sent
 * - sent: Email successfully sent
 * - failed: Email failed after max retries (needs manual intervention)
 */
enum EmailStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
}
