<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper trait for managing fixtures in tests.
 * Provides methods to easily persist and clear test data.
 */
trait FixtureHelper
{
    /**
     * Get the service container.
     * This method is provided by KernelTestCase and WebTestCase.
     */
    abstract protected static function getContainer(): ContainerInterface;

    protected function persistFixtures(object ...$entities): void
    {
        $em = $this->getEntityManager();

        foreach ($entities as $entity) {
            $em->persist($entity);
        }

        $em->flush();
    }

    protected function clearFixtures(): void
    {
        $em = $this->getEntityManager();
        $em->clear();
    }

    protected function refreshEntity(object $entity): object
    {
        $em = $this->getEntityManager();
        $em->refresh($entity);

        return $entity;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $container = static::getContainer();

        /** @var Registry $doctrine */
        $doctrine = $container->get('doctrine');

        $em = $doctrine->getManager();

        if (!$em instanceof EntityManagerInterface) {
            throw new \RuntimeException('Could not get EntityManager from Doctrine');
        }

        return $em;
    }
}
