<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Http\Middleware;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

/**
 * Correlation ID Middleware.
 *
 * Ensures every request has a unique correlation ID for distributed tracing.
 *
 * Flow:
 * 1. Check if request has X-Correlation-ID header
 * 2. If not, generate a new UUID
 * 3. Store in request attributes
 * 4. Add to response headers
 * 5. Use in logs, events, and external API calls
 *
 * Benefits:
 * - Trace requests across services
 * - Debug production issues
 * - Link events to originating request
 * - Audit trail
 *
 * Headers:
 * - X-Correlation-ID: Unique ID for this request chain
 * - X-Request-ID: Unique ID for this specific request (always generated)
 */
final class CorrelationIdMiddleware implements EventSubscriberInterface
{
    private const CORRELATION_ID_HEADER = 'X-Correlation-ID';
    private const REQUEST_ID_HEADER = 'X-Request-ID';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],   // High priority
            KernelEvents::RESPONSE => ['onKernelResponse', -512], // Low priority
        ];
    }

    /**
     * Process incoming request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Get or generate correlation ID (persists across services)
        $correlationId = $request->headers->get(self::CORRELATION_ID_HEADER);
        if (null === $correlationId || '' === $correlationId) {
            $correlationId = Uuid::v4()->toRfc4122();
        }

        // Always generate a new request ID (unique to this request)
        $requestId = Uuid::v4()->toRfc4122();

        // Store in request attributes for use in controllers/services
        $request->attributes->set('correlation_id', $correlationId);
        $request->attributes->set('request_id', $requestId);

        // Store in request for later access
        $request->headers->set(self::CORRELATION_ID_HEADER, $correlationId);
        $request->headers->set(self::REQUEST_ID_HEADER, $requestId);
    }

    /**
     * Add IDs to response headers.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Add to response headers for client visibility
        /** @var null|string $correlationId */
        $correlationId = $request->attributes->get('correlation_id');

        /** @var null|string $requestId */
        $requestId = $request->attributes->get('request_id');

        if (null !== $correlationId) {
            $response->headers->set(self::CORRELATION_ID_HEADER, $correlationId);
        }

        if (null !== $requestId) {
            $response->headers->set(self::REQUEST_ID_HEADER, $requestId);
        }
    }

    /**
     * Get correlation ID from current request.
     *
     * Utility method for services that need the current correlation ID.
     */
    public static function getCorrelationId(Request $request): string
    {
        $correlationId = $request->attributes->get('correlation_id', 'unknown');

        if (!\is_string($correlationId)) {
            return 'unknown';
        }

        return $correlationId;
    }

    /**
     * Get request ID from current request.
     */
    public static function getRequestId(Request $request): string
    {
        $requestId = $request->attributes->get('request_id', 'unknown');

        if (!\is_string($requestId)) {
            return 'unknown';
        }

        return $requestId;
    }
}
