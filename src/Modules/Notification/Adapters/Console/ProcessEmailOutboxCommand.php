<?php

declare(strict_types=1);

namespace App\Modules\Notification\Adapters\Console;

use App\Modules\Notification\Application\Command\ProcessEmailOutbox;
use App\SharedKernel\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Process Email Outbox Console Command.
 *
 * Manual trigger for processing pending emails in the outbox.
 * Useful for testing or manual intervention.
 *
 * Usage:
 * bin/console app:email:process-outbox
 */
#[AsCommand(
    name: 'app:email:process-outbox',
    description: 'Process pending emails in the outbox'
)]
final class ProcessEmailOutboxCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Processing email outbox...');

        $this->commandBus->dispatch(new ProcessEmailOutbox());

        $io->success('Email outbox processed successfully!');

        return Command::SUCCESS;
    }
}
