<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\EventStore;

use App\SharedKernel\Adapters\EventStore\EventUpcasterChain;
use App\SharedKernel\Domain\EventStore\EventUpcasterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EventUpcasterChain.
 * Tests sequential upcasting of events from old versions to new versions.
 *
 * @internal
 *
 * @covers \App\SharedKernel\Adapters\EventStore\EventUpcasterChain
 */
final class EventUpcasterChainUnitTest extends TestCase
{
    public function testUpcastWithNoUpcastersReturnsOriginalData(): void
    {
        // Arrange
        $chain = new EventUpcasterChain([]);
        $eventData = ['field' => 'value'];

        // Act
        $result = $chain->upcast('SomeEvent', 1, $eventData, 1);

        // Assert
        self::assertSame($eventData, $result);
    }

    public function testUpcastWhenAlreadyAtTargetVersionReturnsOriginalData(): void
    {
        // Arrange
        $upcaster = $this->createMock(EventUpcasterInterface::class);
        $upcaster->method('supports')->willReturn('TestEvent');
        $upcaster->method('fromVersion')->willReturn(1);
        $upcaster->expects(self::never())->method('upcast');

        $chain = new EventUpcasterChain([$upcaster]);
        $eventData = ['field' => 'value'];

        // Act
        $result = $chain->upcast('TestEvent', 2, $eventData, 2);

        // Assert
        self::assertSame($eventData, $result);
    }

    public function testUpcastAppliesSingleUpcaster(): void
    {
        // Arrange
        $upcaster = $this->createMock(EventUpcasterInterface::class);
        $upcaster->method('supports')->willReturn('TestEvent');
        $upcaster->method('fromVersion')->willReturn(1);
        $upcaster->method('toVersion')->willReturn(2);
        $upcaster->expects(self::once())
            ->method('upcast')
            ->with(['old_field' => 'value'])
            ->willReturn(['new_field' => 'value'])
        ;

        $chain = new EventUpcasterChain([$upcaster]);
        $eventData = ['old_field' => 'value'];

        // Act
        $result = $chain->upcast('TestEvent', 1, $eventData, 2);

        // Assert
        self::assertSame(['new_field' => 'value'], $result);
    }

    public function testUpcastAppliesMultipleUpcastersInSequence(): void
    {
        // Arrange
        $upcaster1to2 = $this->createMock(EventUpcasterInterface::class);
        $upcaster1to2->method('supports')->willReturn('TestEvent');
        $upcaster1to2->method('fromVersion')->willReturn(1);
        $upcaster1to2->method('toVersion')->willReturn(2);
        $upcaster1to2->method('upcast')
            ->with(['v1' => 'data'])
            ->willReturn(['v2' => 'data'])
        ;

        $upcaster2to3 = $this->createMock(EventUpcasterInterface::class);
        $upcaster2to3->method('supports')->willReturn('TestEvent');
        $upcaster2to3->method('fromVersion')->willReturn(2);
        $upcaster2to3->method('toVersion')->willReturn(3);
        $upcaster2to3->method('upcast')
            ->with(['v2' => 'data'])
            ->willReturn(['v3' => 'data'])
        ;

        $chain = new EventUpcasterChain([$upcaster1to2, $upcaster2to3]);
        $eventData = ['v1' => 'data'];

        // Act
        $result = $chain->upcast('TestEvent', 1, $eventData, 3);

        // Assert
        self::assertSame(['v3' => 'data'], $result);
    }

    public function testUpcastSkipsVersionsWithoutUpcasters(): void
    {
        // Arrange
        $upcaster1to2 = $this->createMock(EventUpcasterInterface::class);
        $upcaster1to2->method('supports')->willReturn('TestEvent');
        $upcaster1to2->method('fromVersion')->willReturn(1);
        $upcaster1to2->method('toVersion')->willReturn(2);
        $upcaster1to2->method('upcast')
            ->with(['v1' => 'data'])
            ->willReturn(['v2' => 'data'])
        ;

        // No upcaster for v2 → v3 (schema didn't change)

        $upcaster3to4 = $this->createMock(EventUpcasterInterface::class);
        $upcaster3to4->method('supports')->willReturn('TestEvent');
        $upcaster3to4->method('fromVersion')->willReturn(3);
        $upcaster3to4->method('toVersion')->willReturn(4);
        $upcaster3to4->method('upcast')
            ->with(['v2' => 'data'])
            ->willReturn(['v4' => 'data'])
        ;

        $chain = new EventUpcasterChain([$upcaster1to2, $upcaster3to4]);
        $eventData = ['v1' => 'data'];

        // Act
        $result = $chain->upcast('TestEvent', 1, $eventData, 4);

        // Assert
        self::assertSame(['v4' => 'data'], $result);
    }

    public function testUpcastIgnoresUpcastersForDifferentEventTypes(): void
    {
        // Arrange
        $upcasterForOtherEvent = $this->createMock(EventUpcasterInterface::class);
        $upcasterForOtherEvent->method('supports')->willReturn('OtherEvent');
        $upcasterForOtherEvent->method('fromVersion')->willReturn(1);
        $upcasterForOtherEvent->expects(self::never())->method('upcast');

        $chain = new EventUpcasterChain([$upcasterForOtherEvent]);
        $eventData = ['field' => 'value'];

        // Act
        $result = $chain->upcast('TestEvent', 1, $eventData, 2);

        // Assert
        self::assertSame($eventData, $result);
    }

    public function testUpcastStopsAtTargetVersion(): void
    {
        // Arrange
        $upcaster1to2 = $this->createMock(EventUpcasterInterface::class);
        $upcaster1to2->method('supports')->willReturn('TestEvent');
        $upcaster1to2->method('fromVersion')->willReturn(1);
        $upcaster1to2->method('toVersion')->willReturn(2);
        $upcaster1to2->expects(self::once())
            ->method('upcast')
            ->willReturn(['v2' => 'data'])
        ;

        $upcaster2to3 = $this->createMock(EventUpcasterInterface::class);
        $upcaster2to3->method('supports')->willReturn('TestEvent');
        $upcaster2to3->method('fromVersion')->willReturn(2);
        $upcaster2to3->method('toVersion')->willReturn(3);
        $upcaster2to3->expects(self::never())->method('upcast');

        $chain = new EventUpcasterChain([$upcaster1to2, $upcaster2to3]);
        $eventData = ['v1' => 'data'];

        // Act - Only upcast to version 2, not 3
        $result = $chain->upcast('TestEvent', 1, $eventData, 2);

        // Assert
        self::assertSame(['v2' => 'data'], $result);
    }

    public function testUpcastWithEmptyUpcastersIterable(): void
    {
        // Arrange
        $chain = new EventUpcasterChain([]);
        $eventData = ['field' => 'value'];

        // Act
        $result = $chain->upcast('TestEvent', 1, $eventData, 3);

        // Assert
        self::assertSame($eventData, $result);
    }

    public function testUpcastWithMultipleUpcastersForSameEvent(): void
    {
        // Arrange - Multiple transformations for the same event type
        $upcaster1to2 = $this->createMock(EventUpcasterInterface::class);
        $upcaster1to2->method('supports')->willReturn('UserCreated');
        $upcaster1to2->method('fromVersion')->willReturn(1);
        $upcaster1to2->method('toVersion')->willReturn(2);
        $upcaster1to2->method('upcast')
            ->willReturn(['username' => 'john', 'email' => 'john@example.com'])
        ;

        $upcaster2to3 = $this->createMock(EventUpcasterInterface::class);
        $upcaster2to3->method('supports')->willReturn('UserCreated');
        $upcaster2to3->method('fromVersion')->willReturn(2);
        $upcaster2to3->method('toVersion')->willReturn(3);
        $upcaster2to3->method('upcast')
            ->willReturn(['email' => 'john@example.com', 'full_name' => 'John Doe'])
        ;

        $chain = new EventUpcasterChain([$upcaster1to2, $upcaster2to3]);
        $eventData = ['username' => 'john'];

        // Act
        $result = $chain->upcast('UserCreated', 1, $eventData, 3);

        // Assert
        self::assertSame(['email' => 'john@example.com', 'full_name' => 'John Doe'], $result);
    }

    public function testUpcastPreservesDataWhenNoUpcasterFound(): void
    {
        // Arrange
        $upcaster1to2 = $this->createMock(EventUpcasterInterface::class);
        $upcaster1to2->method('supports')->willReturn('TestEvent');
        $upcaster1to2->method('fromVersion')->willReturn(1);
        $upcaster1to2->method('toVersion')->willReturn(2);
        $upcaster1to2->method('upcast')
            ->willReturn(['v2' => 'data'])
        ;

        // Missing upcaster for v2 → v3
        // But we need to reach v3

        $chain = new EventUpcasterChain([$upcaster1to2]);
        $eventData = ['v1' => 'data'];

        // Act - Should upcast v1→v2, then skip v2→v3 (no upcaster)
        $result = $chain->upcast('TestEvent', 1, $eventData, 3);

        // Assert - Data from v2 should be preserved
        self::assertSame(['v2' => 'data'], $result);
    }
}
