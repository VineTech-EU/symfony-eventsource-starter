<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Bus;

use App\SharedKernel\Application\Bus\EventBusInterface;
use App\SharedKernel\Domain\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SymfonyEventBus implements EventBusInterface
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->eventBus->dispatch($event);
    }

    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
