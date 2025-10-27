<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Application\EventHandler\IncrementReferrerStatsWhenUserApproved;
use App\Modules\User\Domain\Event\UserApproved;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Application\EventHandler\IncrementReferrerStatsWhenUserApproved
 *
 * NOTE: This handler is a stub/documentation example showing when to use Repository in event handlers.
 * It doesn't have actual business logic yet, so tests verify it can be invoked without errors.
 *
 * @internal
 */
final class IncrementReferrerStatsWhenUserApprovedUnitTest extends TestCase
{
    private IncrementReferrerStatsWhenUserApproved $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new IncrementReferrerStatsWhenUserApproved();
    }

    public function testInvokeCanBeCalledWithoutErrors(): void
    {
        // Arrange
        $this->expectNotToPerformAssertions();

        $event = new UserApproved(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
        );

        // Act - Handler should execute without throwing exceptions
        ($this->handler)($event);

        // This test verifies the handler stub exists and is callable
        // In a real implementation, this would test repository interaction
    }

    public function testInvokeWithDifferentUserData(): void
    {
        // Arrange
        $this->expectNotToPerformAssertions();

        $event = new UserApproved(
            userId: '660e8400-e29b-41d4-a716-446655440001',
            email: 'admin@example.com',
            name: 'Admin User',
        );

        // Act - Handler should execute without throwing exceptions
        ($this->handler)($event);
    }
}
