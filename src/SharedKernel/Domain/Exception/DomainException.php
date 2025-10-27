<?php

declare(strict_types=1);

namespace App\SharedKernel\Domain\Exception;

/**
 * Base Domain Exception with Structured Context.
 *
 * Philosophy:
 * - Message is STATIC (for grouping in Datadog/Sentry)
 * - Context is DYNAMIC (for debugging)
 * - Implements JsonSerializable for structured logging
 *
 * Usage:
 * throw UserNotFoundException::withId($userId);
 *
 * In Datadog/Sentry:
 * - Message: "User not found in event store"  ← Groups all similar errors
 * - Context: {"user_id": "123-456", "aggregate": "User"}  ← Debug info
 */
abstract class DomainException extends \DomainException implements \JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * Get structured context for logging.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context data.
     *
     * @param array<string, mixed> $additionalContext
     */
    public function addContext(array $additionalContext): void
    {
        $this->context = array_merge($this->context, $additionalContext);
    }

    /**
     * JsonSerializable for Monolog/Datadog.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Set context data.
     *
     * @param array<string, mixed> $context
     */
    protected function setContext(array $context): void
    {
        $this->context = $context;
    }
}
