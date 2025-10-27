<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Logging;

use App\SharedKernel\Domain\DomainEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Logging Middleware for Symfony Messenger.
 *
 * Automatically logs ALL messages (commands, queries, events) and their handlers.
 * This is a MIDDLEWARE - adds logging transparently without touching use cases or handlers.
 *
 * Logs:
 * - Message dispatch (BEFORE handler)
 * - Handler execution (AFTER handler)
 * - Handler failures (ON ERROR)
 *
 * Philosophy:
 * - STATIC messages for grouping
 * - DYNAMIC context for debugging
 * - Zero pollution of business code
 */
final readonly class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        $messageClass = $message::class;
        $messageType = $this->getMessageType($message);

        // Build context
        $context = [
            'message_type' => $messageType,
            'message_class' => $messageClass,
        ];

        // Add specific context for domain events
        if ($message instanceof DomainEvent) {
            $context['event_name'] = $message::getEventName();
            $context['aggregate_id'] = $message->getAggregateId();
            $context['event_id'] = $message->getEventId();
        }

        // Log message handling start
        $this->logger->info('Message handling started', $context);  // ← STATIC message

        $startTime = microtime(true);

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $duration = microtime(true) - $startTime;

            // Get handlers that processed this message
            $handlers = $this->getHandlerNames($envelope);

            // Log success
            $this->logger->info('Message handling succeeded', array_merge($context, [
                'duration_ms' => round($duration * 1000, 2),
                'handlers' => $handlers,
                'handlers_count' => \count($handlers),
            ]));

            return $envelope;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            // Log failure with exception context
            $errorContext = array_merge($context, [
                'duration_ms' => round($duration * 1000, 2),
                'error_message' => $e->getMessage(),
                'error_class' => $e::class,
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            // If exception has context (our DomainException), add it
            if (method_exists($e, 'getContext')) {
                $errorContext['error_context'] = $e->getContext();
            }

            $this->logger->error('Message handling failed', $errorContext);

            throw $e;
        }
    }

    private function getMessageType(object $message): string
    {
        if ($message instanceof DomainEvent) {
            return 'event';
        }

        // Detect command vs query by naming convention
        $className = (new \ReflectionClass($message))->getShortName();

        if (str_ends_with($className, 'Command')) {
            return 'command';
        }

        if (str_ends_with($className, 'Query')) {
            return 'query';
        }

        return 'message';
    }

    /**
     * @return string[]
     */
    private function getHandlerNames(Envelope $envelope): array
    {
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        return array_map(
            static fn (HandledStamp $stamp) => $stamp->getHandlerName(),
            $handledStamps
        );
    }
}
