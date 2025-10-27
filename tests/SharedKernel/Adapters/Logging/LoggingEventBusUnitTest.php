<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\Logging;

use App\SharedKernel\Adapters\Logging\LoggingEventBus;
use App\SharedKernel\Application\Bus\EventBusInterface;
use App\SharedKernel\Domain\DomainEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\SharedKernel\Adapters\Logging\LoggingEventBus
 *
 * @internal
 */
final class LoggingEventBusUnitTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private EventBusInterface&MockObject $decoratedBus;
    private LoggingEventBus $loggingEventBus;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->decoratedBus = $this->createMock(EventBusInterface::class);
        $this->loggingEventBus = new LoggingEventBus($this->decoratedBus, $this->logger);
    }

    public function testDispatchLogsEventBeforeDispatch(): void
    {
        // Given
        $event = $this->createMockEvent();

        // Expect logger to be called BEFORE decorated bus
        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Domain event dispatched',
                self::callback(static function (array $context) {
                    /** @var string $eventClass */
                    $eventClass = $context['event_class'];

                    return $context['event_type'] === 'test.event'
                        && str_contains($eventClass, 'TestEvent') // Updated to match concrete test class
                        && $context['aggregate_id'] === 'test-aggregate-id'
                        && isset($context['event_id'], $context['occurred_on'], $context['payload']);
                })
            )
        ;

        $this->decoratedBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($event)
        ;

        // When
        $this->loggingEventBus->dispatch($event);
    }

    public function testDispatchLogsSuccessAfterDispatch(): void
    {
        // Given
        $event = $this->createMockEvent();

        $loggerCallCount = 0;
        $this->logger
            ->method('info')
            ->willReturnCallback(static function () use (&$loggerCallCount): void {
                ++$loggerCallCount;
            })
        ;

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Domain event dispatch succeeded',
                self::callback(static function (array $context) {
                    return $context['event_type'] === 'test.event'
                        && $context['aggregate_id'] === 'test-aggregate-id';
                })
            )
        ;

        // When
        $this->loggingEventBus->dispatch($event);

        // Then
        self::assertSame(1, $loggerCallCount, 'Logger info should be called once');
    }

    public function testDispatchLogsFailureAndRethrowsException(): void
    {
        // Given
        $event = $this->createMockEvent();
        $exception = new \RuntimeException('Dispatch failed');

        $this->decoratedBus
            ->method('dispatch')
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Domain event dispatch failed',
                self::callback(static function (array $context) {
                    return $context['event_type'] === 'test.event'
                        && $context['aggregate_id'] === 'test-aggregate-id'
                        && $context['error_message'] === 'Dispatch failed'
                        && $context['error_class'] === \RuntimeException::class;
                })
            )
        ;

        // Expect exception to be rethrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dispatch failed');

        // When
        $this->loggingEventBus->dispatch($event);
    }

    public function testDispatchAllLogsBatchDispatch(): void
    {
        // Given
        $event1 = $this->createMockEvent('event1');
        $event2 = $this->createMockEvent('event2');
        $events = [$event1, $event2];

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Domain events batch dispatched',
                self::callback(static function (array $context) {
                    return $context['events_count'] === 2
                        && $context['event_types'] === ['event1', 'event2'];
                })
            )
        ;

        $this->decoratedBus
            ->expects(self::once())
            ->method('dispatchAll')
            ->with($events)
        ;

        // When
        $this->loggingEventBus->dispatchAll($events);
    }

    public function testDispatchAllLogsSuccessAfterBatchDispatch(): void
    {
        // Given
        $events = [$this->createMockEvent(), $this->createMockEvent()];

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Domain events batch dispatch succeeded',
                self::callback(static function (array $context) {
                    return $context['events_count'] === 2;
                })
            )
        ;

        // When
        $this->loggingEventBus->dispatchAll($events);
    }

    public function testDispatchAllLogsFailureAndRethrowsException(): void
    {
        // Given
        $events = [$this->createMockEvent()];
        $exception = new \RuntimeException('Batch dispatch failed');

        $this->decoratedBus
            ->method('dispatchAll')
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Domain events batch dispatch failed',
                self::callback(static function (array $context) {
                    return $context['events_count'] === 1
                        && $context['error_message'] === 'Batch dispatch failed';
                })
            )
        ;

        // Expect exception to be rethrown
        $this->expectException(\RuntimeException::class);

        // When
        $this->loggingEventBus->dispatchAll($events);
    }

    private function createMockEvent(string $eventName = 'test.event'): DomainEvent
    {
        return match ($eventName) {
            'event1' => new TestEvent1(),
            'event2' => new TestEvent2(),
            default => new TestEvent(),
        };
    }
}

/**
 * Concrete test event for LoggingEventBus tests.
 * PHPUnit cannot mock static methods, so we use a real implementation.
 */
final class TestEvent extends DomainEvent
{
    public function getAggregateId(): string
    {
        return 'test-aggregate-id';
    }

    public static function getEventName(): string
    {
        return 'test.event';
    }
}

final class TestEvent1 extends DomainEvent
{
    public function getAggregateId(): string
    {
        return 'test-aggregate-id';
    }

    public static function getEventName(): string
    {
        return 'event1';
    }
}

final class TestEvent2 extends DomainEvent
{
    public function getAggregateId(): string
    {
        return 'test-aggregate-id';
    }

    public static function getEventName(): string
    {
        return 'event2';
    }
}
