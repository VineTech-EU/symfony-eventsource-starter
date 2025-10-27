<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Monitoring;

use Laminas\Diagnostics\Check\AbstractCheck;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\Success;
use Laminas\Diagnostics\Result\Warning;

/**
 * RabbitMQ Health Check.
 *
 * Verifies RabbitMQ connectivity via HTTP Management API.
 * Checks:
 * - Management API accessible
 * - Connection status
 * - Queue health (optional)
 */
final class RabbitMQHealthCheck extends AbstractCheck
{
    public function __construct(
        private readonly string $managementUrl = 'http://rabbitmq:15672/api/healthchecks/node',
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {}

    public function check(): Failure|Success|Warning
    {
        try {
            $context = null;

            if (null !== $this->username && null !== $this->password) {
                $auth = base64_encode("{$this->username}:{$this->password}");
                $context = stream_context_create([
                    'http' => [
                        'header' => "Authorization: Basic {$auth}",
                        'timeout' => 2,
                    ],
                ]);
            } else {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 2,
                    ],
                ]);
            }

            $response = @file_get_contents($this->managementUrl, false, $context);

            if (false === $response) {
                return new Failure('RabbitMQ Management API not accessible');
            }

            /** @var null|array<string, mixed> $data */
            $data = json_decode($response, true);

            if (null === $data) {
                return new Failure('RabbitMQ returned invalid JSON');
            }

            if (isset($data['status']) && 'ok' === $data['status']) {
                return new Success('RabbitMQ is healthy');
            }

            $status = $data['status'] ?? 'unknown';
            if (!\is_string($status)) {
                $status = 'unknown';
            }

            return new Warning('RabbitMQ returned unexpected status: ' . $status);
        } catch (\Throwable $e) {
            return new Failure('RabbitMQ check failed: ' . $e->getMessage());
        }
    }

    public function getLabel(): string
    {
        return 'RabbitMQ Health Check';
    }
}
