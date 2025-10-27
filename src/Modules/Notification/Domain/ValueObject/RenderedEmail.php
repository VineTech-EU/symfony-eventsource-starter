<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\ValueObject;

/**
 * Rendered Email DTO.
 *
 * Holds both HTML and text versions of a rendered email template.
 * Immutable value object.
 */
final readonly class RenderedEmail
{
    public function __construct(
        public string $html,
        public string $text,
    ) {}
}
