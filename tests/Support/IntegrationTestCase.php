<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests.
 * Tests that involve multiple components working together (repository, services, etc.)
 * but don't require HTTP layer.
 *
 * Note: Each test is automatically wrapped in a transaction by DAMA Doctrine Test Bundle.
 * Changes are rolled back after each test, ensuring perfect isolation.
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    use FixtureHelper;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    /**
     * Restore exception handler after each test.
     *
     * Symfony Kernel modifies the exception handler when booting.
     * This is normal behavior, but PHPUnit marks tests as "risky" if handlers aren't restored.
     * We explicitly restore the handler here to avoid false positives.
     */
    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }
}
