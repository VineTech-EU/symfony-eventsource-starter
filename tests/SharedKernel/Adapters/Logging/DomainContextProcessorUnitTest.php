<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\Logging;

use App\SharedKernel\Adapters\Logging\DomainContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\SharedKernel\Adapters\Logging\DomainContextProcessor
 *
 * @internal
 */
final class DomainContextProcessorUnitTest extends TestCase
{
    public function testEnrichesLogRecordWithApplicationContext(): void
    {
        // Given
        $processor = new DomainContextProcessor('test-app', 'test-env');
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then
        self::assertSame('test-app', $enrichedRecord->extra['app']);
        self::assertSame('test-env', $enrichedRecord->extra['env']);
    }

    public function testAddsDefaultApplicationNameAndEnvironment(): void
    {
        // Given
        $processor = new DomainContextProcessor();
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then
        self::assertSame('symfony-eventsource', $enrichedRecord->extra['app']);
        self::assertSame('dev', $enrichedRecord->extra['env']);
    }

    public function testAddsMicroserviceContext(): void
    {
        // Given
        $processor = new DomainContextProcessor();
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then
        self::assertArrayHasKey('microservice', $enrichedRecord->extra);
        self::assertSame('user-service', $enrichedRecord->extra['microservice']);
    }

    public function testAddsIsoTimestamp(): void
    {
        // Given
        $processor = new DomainContextProcessor();
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then
        self::assertArrayHasKey('timestamp_iso', $enrichedRecord->extra);

        /** @var string $timestampIso */
        $timestampIso = $enrichedRecord->extra['timestamp_iso'];

        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/',
            $timestampIso
        );

        // Verify it's a valid ISO 8601 timestamp
        $timestamp = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $timestampIso);
        self::assertInstanceOf(\DateTimeImmutable::class, $timestamp);
    }

    public function testAddsMemoryUsage(): void
    {
        // Given
        $processor = new DomainContextProcessor();
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then
        self::assertArrayHasKey('memory_usage_mb', $enrichedRecord->extra);
        self::assertIsFloat($enrichedRecord->extra['memory_usage_mb']);
        self::assertGreaterThan(0, $enrichedRecord->extra['memory_usage_mb']);
    }

    public function testDoesNotModifyOriginalRecordData(): void
    {
        // Given
        $processor = new DomainContextProcessor('test-app', 'prod');
        $originalMessage = 'Test log message';
        $originalContext = ['user_id' => '123'];
        $record = $this->createLogRecord($originalMessage, $originalContext);

        // When
        $enrichedRecord = $processor($record);

        // Then - Original message and context should remain unchanged
        self::assertSame($originalMessage, $enrichedRecord->message);
        self::assertSame($originalContext, $enrichedRecord->context);
    }

    public function testAddsAllExpectedExtraFields(): void
    {
        // Given
        $processor = new DomainContextProcessor();
        $record = $this->createLogRecord();

        // When
        $enrichedRecord = $processor($record);

        // Then - Verify all expected fields are present
        $expectedFields = ['app', 'env', 'microservice', 'timestamp_iso', 'memory_usage_mb'];

        foreach ($expectedFields as $field) {
            self::assertArrayHasKey(
                $field,
                $enrichedRecord->extra,
                "Expected extra field '{$field}' to be present"
            );
        }
    }

    public function testWorksWithDifferentLogLevels(): void
    {
        // Given
        $processor = new DomainContextProcessor();

        $levels = [
            Level::Debug,
            Level::Info,
            Level::Warning,
            Level::Error,
            Level::Critical,
        ];

        foreach ($levels as $level) {
            // When
            $record = $this->createLogRecord('Test message', [], $level);
            $enrichedRecord = $processor($record);

            // Then
            self::assertSame($level, $enrichedRecord->level);
            self::assertArrayHasKey('app', $enrichedRecord->extra);
            self::assertArrayHasKey('env', $enrichedRecord->extra);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function createLogRecord(
        string $message = 'Test message',
        array $context = [],
        Level $level = Level::Info
    ): LogRecord {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: $level,
            message: $message,
            context: $context,
            extra: []
        );
    }
}
