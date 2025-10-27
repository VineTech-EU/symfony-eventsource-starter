<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Bus;

interface CommandHandlerInterface
{
    public function __invoke(CommandInterface $command): void;
}
