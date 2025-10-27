<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Bus;

use App\SharedKernel\Application\Bus\CommandBusInterface;
use App\SharedKernel\Application\Bus\CommandInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyCommandBus implements CommandBusInterface
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function dispatch(CommandInterface $command): void
    {
        $this->commandBus->dispatch($command);
    }
}
