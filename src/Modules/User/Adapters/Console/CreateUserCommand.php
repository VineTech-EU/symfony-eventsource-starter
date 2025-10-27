<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Console;

use App\Modules\User\Application\Command\DTO\CreateUserRequest;
use App\Modules\User\Application\UseCase\CreateUser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * CLI Command to create a user.
 *
 * This demonstrates that Use Cases can be called from ANY entry point:
 * - HTTP Controllers
 * - CLI Commands (this one)
 * - Message Bus Handlers
 * - Event Handlers
 * - Tests
 *
 * The same business logic (CreateUser use case) is reused everywhere.
 * Now uses Request DTOs for type-safety.
 */
#[AsCommand(
    name: 'app:user:create',
    description: 'Create a new user from CLI',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly CreateUser $createUser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('name', InputArgument::REQUIRED, 'User name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailArg = $input->getArgument('email');
        $nameArg = $input->getArgument('name');

        if (!\is_string($emailArg) || !\is_string($nameArg)) {
            $io->error('Email and name must be strings');

            return Command::FAILURE;
        }

        $userId = Uuid::v4()->toRfc4122();

        try {
            // Create Request DTO for type-safety
            $request = new CreateUserRequest(
                email: $emailArg,
                name: $nameArg,
            );

            // Call the SAME use case as HTTP controller
            $this->createUser->execute($userId, $request);

            $io->success(\sprintf('User created with ID: %s', $userId));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(\sprintf('Failed to create user: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
