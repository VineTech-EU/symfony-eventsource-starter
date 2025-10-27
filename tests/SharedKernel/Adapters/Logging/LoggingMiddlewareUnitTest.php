<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\Logging;

use App\SharedKernel\Adapters\Logging\LoggingMiddleware;
use App\SharedKernel\Domain\DomainEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @covers \App\SharedKernel\Adapters\Logging\LoggingMiddleware
 *
 * @internal
 */
final class LoggingMiddlewareUnitTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private LoggingMiddleware $middleware;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->middleware = new LoggingMiddleware($this->logger);
    }

    public function testHandleLogsMessageStarted(): void
    {
        // Given
        $message = new TestCommand();
        $envelope = new Envelope($message);
        $stack = $this->createMockStack($envelope);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $msg, array $context): void {
                if ($msg === 'Message handling started') {
                    self::assertSame('command', $context['message_type']);
                    self::assertSame(TestCommand::class, $context['message_class']);
                }
            })
        ;

        // When
        $this->middleware->handle($envelope, $stack);
    }

    public function testHandleLogsMessageSucceeded(): void
    {
        // Given
        $message = new TestCommand();
        $envelope = new Envelope($message);
        $handledEnvelope = $envelope->with(new HandledStamp('result', 'TestHandler::handle'));
        $stack = $this->createMockStack($handledEnvelope);

        $loggerCallCount = 0;
        $this->logger
            ->method('info')
            ->willReturnCallback(static function (string $msg, array $context) use (&$loggerCallCount): void {
                ++$loggerCallCount;

                if ($msg === 'Message handling succeeded') {
                    self::assertArrayHasKey('duration_ms', $context);
                    self::assertArrayHasKey('handlers', $context);
                    self::assertArrayHasKey('handlers_count', $context);
                    self::assertSame(1, $context['handlers_count']);

                    /** @var list<string> $handlers */
                    $handlers = $context['handlers'];
                    self::assertContains('TestHandler::handle', $handlers);
                }
            })
        ;

        // When
        $this->middleware->handle($envelope, $stack);

        // Then
        self::assertSame(2, $loggerCallCount, 'Logger should be called twice (started + succeeded)');
    }

    public function testHandleLogsDomainEventWithExtraContext(): void
    {
        // Given
        $event = $this->createMockDomainEvent();
        $envelope = new Envelope($event);
        $stack = $this->createMockStack($envelope);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $msg, array $context): void {
                if ($msg === 'Message handling started') {
                    self::assertSame('event', $context['message_type']);
                    self::assertSame('test.event', $context['event_name']);
                    self::assertSame('test-aggregate-id', $context['aggregate_id']);
                    self::assertArrayHasKey('event_id', $context); // Event ID is auto-generated
                    self::assertIsString($context['event_id']);
                }
            })
        ;

        // When
        $this->middleware->handle($envelope, $stack);
    }

    public function testHandleLogsFailureAndRethrowsException(): void
    {
        // Given
        $message = new TestCommand();
        $envelope = new Envelope($message);
        $exception = new \RuntimeException('Handler failed');
        $stack = $this->createMockStackWithException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Message handling failed',
                self::callback(static function (array $context) {
                    return $context['message_type'] === 'command'
                        && $context['error_message'] === 'Handler failed'
                        && $context['error_class'] === \RuntimeException::class
                        && isset($context['duration_ms'], $context['error_file'], $context['error_line']);
                })
            )
        ;

        // Expect exception to be rethrown
        $this->expectException(\RuntimeException::class);

        // When
        $this->middleware->handle($envelope, $stack);
    }

    public function testHandleLogsExceptionWithContextIfAvailable(): void
    {
        // Given
        $message = new TestCommand();
        $envelope = new Envelope($message);
        $exception = new TestExceptionWithContext('Error with context');
        $stack = $this->createMockStackWithException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Message handling failed',
                self::callback(static function (array $context) {
                    return isset($context['error_context'])
                        && $context['error_context'] === ['user_id' => '123'];
                })
            )
        ;

        // Expect exception to be rethrown
        $this->expectException(TestExceptionWithContext::class);

        // When
        $this->middleware->handle($envelope, $stack);
    }

    public function testDetectsQueryMessageType(): void
    {
        // Given
        $message = new TestQuery();
        $envelope = new Envelope($message);
        $stack = $this->createMockStack($envelope);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $msg, array $context): void {
                if ($msg === 'Message handling started') {
                    self::assertSame('query', $context['message_type']);
                }
            })
        ;

        // When
        $this->middleware->handle($envelope, $stack);
    }

    public function testDetectsGenericMessageType(): void
    {
        // Given
        $message = new TestGenericMessage();
        $envelope = new Envelope($message);
        $stack = $this->createMockStack($envelope);

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $msg, array $context): void {
                if ($msg === 'Message handling started') {
                    self::assertSame('message', $context['message_type']);
                }
            })
        ;

        // When
        $this->middleware->handle($envelope, $stack);
    }

    private function createMockStack(Envelope $returnEnvelope): StackInterface
    {
        return new class($returnEnvelope) implements StackInterface {
            public function __construct(private Envelope $returnEnvelope) {}

            public function next(): MiddlewareInterface
            {
                return new class($this->returnEnvelope) implements MiddlewareInterface {
                    public function __construct(private Envelope $returnEnvelope) {}

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $this->returnEnvelope;
                    }
                };
            }
        };
    }

    private function createMockStackWithException(\Throwable $exception): StackInterface
    {
        return new class($exception) implements StackInterface {
            public function __construct(private \Throwable $exception) {}

            public function next(): MiddlewareInterface
            {
                return new class($this->exception) implements MiddlewareInterface {
                    public function __construct(private \Throwable $exception) {}

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        throw $this->exception;
                    }
                };
            }
        };
    }

    private function createMockDomainEvent(): DomainEvent
    {
        return new TestMiddlewareEvent();
    }
}

// Test fixtures

final class TestCommand {}

final class TestQuery {}

final class TestGenericMessage {}

final class TestExceptionWithContext extends \RuntimeException
{
    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return ['user_id' => '123'];
    }
}

/**
 * Concrete test event for LoggingMiddleware tests.
 * PHPUnit cannot mock static methods, so we use a real implementation.
 */
final class TestMiddlewareEvent extends DomainEvent
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
