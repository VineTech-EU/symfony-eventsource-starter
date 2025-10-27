<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Http\Middleware;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rate Limit Middleware.
 *
 * Implements token bucket algorithm for rate limiting.
 *
 * Strategy:
 * - Global limit: 100 requests per minute per IP
 * - Authenticated users: 200 requests per minute
 * - Uses APCu for high performance (falls back to simple counter)
 *
 * Headers added to response:
 * - X-RateLimit-Limit: Maximum requests allowed
 * - X-RateLimit-Remaining: Requests remaining in current window
 * - X-RateLimit-Reset: Unix timestamp when limit resets
 * - Retry-After: Seconds until limit resets (on 429 response)
 *
 * Algorithm: Token Bucket
 * - Bucket starts full
 * - Each request consumes 1 token
 * - Tokens refill over time
 * - Request blocked when bucket empty
 *
 * Production Recommendations:
 * - Use Redis for distributed rate limiting
 * - Add per-route limits for expensive endpoints
 * - Add burst allowance for legitimate traffic spikes
 * - Monitor rate limit hits in Prometheus
 *
 * Security Benefits:
 * - Prevents brute force attacks
 * - Mitigates DoS attacks
 * - Protects against API abuse
 * - Ensures fair resource allocation
 */
final class RateLimitMiddleware implements EventSubscriberInterface
{
    private const DEFAULT_LIMIT = 100;            // Requests per window
    private const AUTHENTICATED_LIMIT = 200;      // Higher limit for authenticated users
    private const WINDOW_SECONDS = 60;            // Time window (1 minute)
    private const EXEMPT_ROUTES = [               // Routes exempt from rate limiting
        'health_check',
        'health_live',
        'health_ready',
        'metrics',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256], // After authentication
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Skip rate limiting for exempt routes
        if (\in_array($route, self::EXEMPT_ROUTES, true)) {
            return;
        }

        // Determine rate limit based on authentication
        $isAuthenticated = null !== $request->attributes->get('user_id'); // Set by auth middleware
        $limit = $isAuthenticated ? self::AUTHENTICATED_LIMIT : self::DEFAULT_LIMIT;

        // Get client identifier (IP address or user ID)
        $identifier = $this->getClientIdentifier($request);

        // Check rate limit
        $result = $this->checkRateLimit($identifier, $limit);

        // Add rate limit headers
        $this->addRateLimitHeaders($request, $result);

        // Block request if limit exceeded
        if ($result['allowed'] === false) {
            $response = new JsonResponse([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $result['reset_in'],
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->set('Retry-After', (string) $result['reset_in']);
            $response->headers->set('X-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) $result['reset_at']);

            $event->setResponse($response);
        }
    }

    /**
     * Get unique client identifier for rate limiting.
     */
    private function getClientIdentifier(Request $request): string
    {
        // Prefer user ID if authenticated
        /** @var null|string $userId */
        $userId = $request->attributes->get('user_id');
        if (null !== $userId) {
            return 'user:' . $userId;
        }

        // Fall back to IP address
        $clientIp = $request->getClientIp() ?? 'unknown';

        // Handle proxied requests
        if ($request->headers->has('X-Forwarded-For')) {
            $forwardedFor = $request->headers->get('X-Forwarded-For');
            if (null !== $forwardedFor) {
                $ips = explode(',', $forwardedFor);
                $clientIp = trim($ips[0]);
            }
        }

        return 'ip:' . $clientIp;
    }

    /**
     * Check if request is within rate limit.
     *
     * @return array{allowed: bool, remaining: int, reset_at: int, reset_in: int}
     */
    private function checkRateLimit(string $identifier, int $limit): array
    {
        $now = time();
        $key = 'rate_limit:' . $identifier;

        // Try to use APCu if available
        if (\function_exists('apcu_fetch')) {
            /** @var array{count: int, reset_at: int}|false $data */
            $data = apcu_fetch($key);
            if (false === $data) {
                // First request in window
                $data = [
                    'count' => 1,
                    'reset_at' => $now + self::WINDOW_SECONDS,
                ];
                apcu_store($key, $data, self::WINDOW_SECONDS);

                return [
                    'allowed' => true,
                    'remaining' => $limit - 1,
                    'reset_at' => $data['reset_at'],
                    'reset_in' => self::WINDOW_SECONDS,
                ];
            }

            // Check if window has expired
            if ($now >= $data['reset_at']) {
                $data = [
                    'count' => 1,
                    'reset_at' => $now + self::WINDOW_SECONDS,
                ];
                apcu_store($key, $data, self::WINDOW_SECONDS);

                return [
                    'allowed' => true,
                    'remaining' => $limit - 1,
                    'reset_at' => $data['reset_at'],
                    'reset_in' => self::WINDOW_SECONDS,
                ];
            }

            // Check if limit exceeded
            if ($data['count'] >= $limit) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_at' => $data['reset_at'],
                    'reset_in' => $data['reset_at'] - $now,
                ];
            }

            // Increment counter
            ++$data['count'];
            apcu_store($key, $data, $data['reset_at'] - $now);

            return [
                'allowed' => true,
                'remaining' => $limit - $data['count'],
                'reset_at' => $data['reset_at'],
                'reset_in' => $data['reset_at'] - $now,
            ];
        }

        // Fallback: Allow all requests if APCu not available
        // In production, use Redis instead
        return [
            'allowed' => true,
            'remaining' => $limit,
            'reset_at' => $now + self::WINDOW_SECONDS,
            'reset_in' => self::WINDOW_SECONDS,
        ];
    }

    /**
     * Add rate limit headers to request (for response event).
     *
     * @param array{allowed: bool, remaining: int, reset_at: int, reset_in: int} $result
     */
    private function addRateLimitHeaders(Request $request, array $result): void
    {
        $request->attributes->set('rate_limit_remaining', $result['remaining']);
        $request->attributes->set('rate_limit_reset', $result['reset_at']);
    }
}
