<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Domain\DomainEvent;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Custom normalizer for DomainEvent deserialization.
 *
 * Handles:
 * - Constructor parameter mapping from snake_case to camelCase
 * - Reflection-based instantiation
 * - Proper type conversion
 */
final readonly class DomainEventNormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DomainEvent
    {
        if (!\is_array($data)) {
            throw new \InvalidArgumentException('Data must be an array for DomainEvent deserialization');
        }

        if (!is_a($type, DomainEvent::class, true)) {
            throw new \InvalidArgumentException(\sprintf('Type "%s" must be a DomainEvent class', $type));
        }

        // Get constructor parameters via reflection
        $reflection = new \ReflectionClass($type);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            throw new \RuntimeException(\sprintf('Event class %s has no constructor', $type));
        }

        $parameters = $constructor->getParameters();
        $args = [];

        // Map snake_case data keys to camelCase constructor parameters
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            $snakeCaseName = $this->camelToSnake($paramName);

            // Try both camelCase and snake_case keys
            if (isset($data[$paramName])) {
                $args[] = $data[$paramName];
            } elseif (isset($data[$snakeCaseName])) {
                $args[] = $data[$snakeCaseName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                throw new \RuntimeException(\sprintf(
                    'Missing required parameter "%s" for event %s',
                    $paramName,
                    $type
                ));
            }
        }

        // Instantiate via constructor
        return $reflection->newInstanceArgs($args);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, DomainEvent::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DomainEvent::class => true,
        ];
    }

    /**
     * Convert camelCase to snake_case.
     */
    private function camelToSnake(string $input): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_$0', lcfirst($input)));
    }
}
