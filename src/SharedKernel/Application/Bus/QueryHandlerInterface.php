<?php

declare(strict_types=1);

namespace App\SharedKernel\Application\Bus;

interface QueryHandlerInterface
{
    public function __invoke(QueryInterface $query): mixed;
}
