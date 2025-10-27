<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Bus;

use App\SharedKernel\Domain\DomainEvent;

interface EventBusInterface
{
    public function dispatch(DomainEvent $event): void;

    /**
     * @param DomainEvent[] $events
     */
    public function dispatchAll(array $events): void;
}
