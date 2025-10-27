<?php

declare(strict_types=1);

namespace App\Modules\Notification\Adapters\Scheduler;

use App\Modules\Notification\Application\Command\ProcessEmailOutbox;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Email Outbox Schedule.
 *
 * Scheduler that triggers ProcessEmailOutbox command every 30 seconds.
 *
 * This ensures pending emails are processed quickly without overwhelming the system.
 *
 * How it works:
 * 1. Symfony Scheduler runs this schedule
 * 2. Every 30 seconds, dispatches ProcessEmailOutbox command
 * 3. ProcessEmailOutboxHandler picks up pending emails and sends them
 * 4. Failed emails retry automatically (up to 5 attempts)
 *
 * To run the scheduler:
 * ```bash
 * bin/console messenger:consume scheduler_default
 * ```
 *
 * Or add to docker-compose.yml workers:
 * ```yaml
 * workers:
 *   command: bin/console messenger:consume scheduler_default async_events async_commands --time-limit=3600
 * ```
 */
#[AsSchedule('email_outbox')]
final readonly class EmailOutboxSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('30 seconds', new ProcessEmailOutbox())
            )
        ;
    }
}
