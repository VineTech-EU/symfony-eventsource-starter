<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Bus;

use App\SharedKernel\Application\Bus\QueryBusInterface;
use App\SharedKernel\Application\Bus\QueryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class SymfonyQueryBus implements QueryBusInterface
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function ask(QueryInterface $query): mixed
    {
        $envelope = $this->queryBus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);

        return $handledStamp?->getResult();
    }
}
