<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Query;

use App\SharedKernel\Application\Bus\QueryInterface;

final readonly class GetUserQuery implements QueryInterface
{
    public function __construct(
        public string $userId,
    ) {}
}
