<?php

declare(strict_types=1);

namespace App\Modules\User\Application\Command;

use App\SharedKernel\Application\Bus\CommandInterface;

final readonly class ChangeUserEmailCommand implements CommandInterface
{
    public function __construct(
        public string $userId,
        public string $newEmail,
    ) {}
}
