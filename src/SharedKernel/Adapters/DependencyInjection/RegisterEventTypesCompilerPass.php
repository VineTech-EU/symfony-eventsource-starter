<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\DependencyInjection;

use App\SharedKernel\Adapters\EventStore\EventTypeRegistry;
use App\SharedKernel\Domain\DomainEvent;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * Compiler Pass for auto-discovery of DomainEvents.
 *
 * Scans all modules for DomainEvent classes and automatically registers
 * their event names in the EventTypeRegistry.
 *
 * This enables:
 * - Zero-config event registration
 * - Refactoring-safe event storage (stable event names)
 * - Automatic event name → FQCN mapping
 */
final class RegisterEventTypesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(EventTypeRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(EventTypeRegistry::class);

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');

        // Scan for DomainEvent classes in all modules
        $eventClasses = $this->findEventClasses($projectDir);

        foreach ($eventClasses as $eventClass) {
            try {
                $reflection = new \ReflectionClass($eventClass);

                // Skip abstract classes
                if ($reflection->isAbstract()) {
                    continue;
                }

                // Get event name via static call (no file parsing needed!)
                /** @var class-string<DomainEvent> $eventClass */
                $eventName = $eventClass::getEventName();

                // Register event name → FQCN mapping
                $registry->addMethodCall('register', [$eventName, $eventClass]);
            } catch (\Throwable) {
                // Skip classes that can't be reflected or have errors calling static method
                continue;
            }
        }
    }

    /**
     * Find all DomainEvent classes in the project.
     *
     * @return list<class-string<DomainEvent>>
     */
    private function findEventClasses(string $projectDir): array
    {
        $eventClasses = [];

        $finder = new Finder();
        $finder->files()
            ->in($projectDir . '/src/Modules/*/Domain/Event')
            ->name('*.php')
            ->notName('*Interface.php')
            ->notName('*Trait.php')
        ;

        foreach ($finder as $file) {
            $namespace = $this->extractNamespace($file->getContents());
            $className = $file->getBasename('.php');

            if (null === $namespace) {
                continue;
            }

            $fqcn = $namespace . '\\' . $className;

            if (class_exists($fqcn) && is_subclass_of($fqcn, DomainEvent::class)) {
                $eventClasses[] = $fqcn;
            }
        }

        return $eventClasses;
    }

    /**
     * Extract namespace from PHP file contents.
     */
    private function extractNamespace(string $contents): ?string
    {
        if (1 === preg_match('/namespace\s+([^;]+);/', $contents, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
