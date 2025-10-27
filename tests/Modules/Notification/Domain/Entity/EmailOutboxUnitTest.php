<?php

declare(strict_types=1);

namespace App\Tests\Modules\Notification\Domain\Entity;

use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\ValueObject\EmailStatus;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\Notification\Domain\Entity\EmailOutbox
 *
 * @internal
 */
final class EmailOutboxUnitTest extends TestCase
{
    public function testCreateInitializesEmailWithPendingStatus(): void
    {
        // Act
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-123',
            emailType: 'welcome',
            recipientEmail: 'john@example.com',
            recipientName: 'John Doe',
            subject: 'Welcome!',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Assert
        self::assertNotEmpty($email->getId());
        self::assertSame('event-123', $email->getEventId());
        self::assertSame('welcome', $email->getEmailType());
        self::assertSame('john@example.com', $email->getRecipientEmail());
        self::assertSame('John Doe', $email->getRecipientName());
        self::assertSame('Welcome!', $email->getSubject());
        self::assertSame('<html>Welcome</html>', $email->getHtmlBody());
        self::assertSame('Welcome', $email->getTextBody());
        self::assertSame(EmailStatus::PENDING, $email->getStatus());
        self::assertSame(0, $email->getAttempts());
        self::assertNull($email->getLastError());
        self::assertInstanceOf(\DateTimeImmutable::class, $email->getCreatedAt());
        self::assertNull($email->getSentAt());
        self::assertTrue($email->canRetry());
    }

    public function testCreateWithNullOptionalFields(): void
    {
        // Act
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-456',
            emailType: 'notification',
            recipientEmail: 'jane@example.com',
            recipientName: null,
            subject: 'Notification',
            htmlBody: '<html>Notification</html>',
            textBody: null,
        );

        // Assert
        self::assertNull($email->getRecipientName());
        self::assertNull($email->getTextBody());
    }

    public function testMarkAsSentUpdatesStatusAndSentAt(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-789',
            emailType: 'welcome',
            recipientEmail: 'bob@example.com',
            recipientName: 'Bob',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act
        $email->markAsSent();

        // Assert
        self::assertSame(EmailStatus::SENT, $email->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $email->getSentAt());
        self::assertNull($email->getLastError());
        self::assertFalse($email->canRetry());
    }

    public function testMarkAsFailedIncrementsAttemptsAndSetsError(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-111',
            emailType: 'welcome',
            recipientEmail: 'alice@example.com',
            recipientName: 'Alice',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act
        $email->markAsFailed('Connection timeout');

        // Assert
        self::assertSame(1, $email->getAttempts());
        self::assertSame('Connection timeout', $email->getLastError());
        self::assertSame(EmailStatus::PENDING, $email->getStatus());
        self::assertTrue($email->canRetry());
    }

    public function testMarkAsFailedAfterMaxAttemptsChangesStatusToFailed(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-222',
            emailType: 'welcome',
            recipientEmail: 'charlie@example.com',
            recipientName: 'Charlie',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act - Simulate 5 failed attempts
        $email->markAsFailed('Error 1');
        $email->markAsFailed('Error 2');
        $email->markAsFailed('Error 3');
        $email->markAsFailed('Error 4');
        $email->markAsFailed('Error 5');

        // Assert
        self::assertSame(5, $email->getAttempts());
        self::assertSame('Error 5', $email->getLastError());
        self::assertSame(EmailStatus::FAILED, $email->getStatus());
        self::assertFalse($email->canRetry());
    }

    public function testCanRetryReturnsTrueForPendingEmailWithLessThanMaxAttempts(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-333',
            emailType: 'welcome',
            recipientEmail: 'dave@example.com',
            recipientName: 'Dave',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act & Assert - 0 attempts
        self::assertTrue($email->canRetry());

        // Act & Assert - 1 attempt
        $email->markAsFailed('Error 1');
        self::assertTrue($email->canRetry());

        // Act & Assert - 4 attempts (still can retry)
        $email->markAsFailed('Error 2');
        $email->markAsFailed('Error 3');
        $email->markAsFailed('Error 4');
        self::assertTrue($email->canRetry());
    }

    public function testCanRetryReturnsFalseAfterMaxAttempts(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-444',
            emailType: 'welcome',
            recipientEmail: 'eve@example.com',
            recipientName: 'Eve',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act - 5 failed attempts
        for ($i = 1; $i <= 5; ++$i) {
            $email->markAsFailed("Error {$i}");
        }

        // Assert
        self::assertFalse($email->canRetry());
    }

    public function testCanRetryReturnsFalseForSentEmail(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-555',
            emailType: 'welcome',
            recipientEmail: 'frank@example.com',
            recipientName: 'Frank',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act
        $email->markAsSent();

        // Assert
        self::assertFalse($email->canRetry());
    }

    public function testMarkAsFailedUpdatesLastErrorWithMostRecentError(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-666',
            emailType: 'welcome',
            recipientEmail: 'grace@example.com',
            recipientName: 'Grace',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act
        $email->markAsFailed('First error');
        $email->markAsFailed('Second error');
        $email->markAsFailed('Third error');

        // Assert
        self::assertSame('Third error', $email->getLastError());
        self::assertSame(3, $email->getAttempts());
    }

    public function testMarkAsSentClearsLastError(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-777',
            emailType: 'welcome',
            recipientEmail: 'henry@example.com',
            recipientName: 'Henry',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        $email->markAsFailed('Some error');

        // Act
        $email->markAsSent();

        // Assert
        self::assertNull($email->getLastError());
    }
}
