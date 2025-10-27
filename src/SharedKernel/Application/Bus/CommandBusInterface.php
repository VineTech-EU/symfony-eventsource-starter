<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Bus;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): void;
}
