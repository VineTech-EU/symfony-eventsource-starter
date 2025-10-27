<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Command;

use App\SharedKernel\Application\Bus\CommandInterface;

/**
 * Process Email Outbox Command.
 *
 * Triggers the consumer to process pending emails in the outbox.
 *
 * This command is dispatched:
 * - By Symfony Scheduler (every 30 seconds)
 * - Manually via CLI: bin/console app:email:process-outbox
 * - For testing/debugging
 *
 * The handler (ProcessEmailOutboxHandler) will:
 * 1. Fetch pending emails from outbox
 * 2. Send each email via EmailSender
 * 3. Update status (sent/failed)
 * 4. Retry failed emails (up to 5 attempts)
 */
final readonly class ProcessEmailOutbox implements CommandInterface
{
    // Empty command - just a trigger
}
