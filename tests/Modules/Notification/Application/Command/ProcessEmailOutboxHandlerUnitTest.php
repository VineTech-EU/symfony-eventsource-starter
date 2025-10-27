<?php

declare(strict_types=1);

namespace App\Tests\Modules\Notification\Application\Command;

use App\Modules\Notification\Application\Command\ProcessEmailOutbox;
use App\Modules\Notification\Application\Command\ProcessEmailOutboxHandler;
use App\Modules\Notification\Application\Service\EmailSenderInterface;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\EmailStatus;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \App\Modules\Notification\Application\Command\ProcessEmailOutboxHandler
 *
 * @internal
 */
final class ProcessEmailOutboxHandlerUnitTest extends TestCase
{
    private EmailOutboxRepositoryInterface&MockObject $outbox;
    private EmailSenderInterface&MockObject $sender;
    private LoggerInterface&MockObject $logger;
    private ProcessEmailOutboxHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outbox = $this->createMock(EmailOutboxRepositoryInterface::class);
        $this->sender = $this->createMock(EmailSenderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ProcessEmailOutboxHandler(
            $this->outbox,
            $this->sender,
            $this->logger
        );
    }

    public function testInvokeProcessesNoPendingEmailsAndLogsDebug(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->with(100)
            ->willReturn([])
        ;

        $this->logger
            ->expects(self::once())
            ->method('debug')
            ->with('No pending emails in outbox')
        ;

        $this->sender->expects(self::never())->method('send');
        $this->outbox->expects(self::never())->method('update');

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }

    public function testInvokeSendsPendingEmailsAndMarksAsSent(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-1',
            emailType: 'welcome',
            recipientEmail: 'john@example.com',
            recipientName: 'John Doe',
            subject: 'Welcome!',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-2',
            emailType: 'notification',
            recipientEmail: 'jane@example.com',
            recipientName: 'Jane Smith',
            subject: 'Notification',
            htmlBody: '<html>Notification</html>',
            textBody: 'Notification'
        );

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->with(100)
            ->willReturn([$email1, $email2])
        ;

        $this->sender
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(static function (string $email, string $subject): void {
                // Verify correct recipients
                self::assertContains($email, ['john@example.com', 'jane@example.com']);
            })
        ;

        $this->outbox
            ->expects(self::exactly(2))
            ->method('update')
            ->willReturnCallback(static function (EmailOutbox $email): void {
                // Verify emails are marked as sent
                self::assertSame(EmailStatus::SENT, $email->getStatus());
                self::assertNotNull($email->getSentAt());
            })
        ;

        $this->logger
            ->expects(self::exactly(3))
            ->method('info')
            ->willReturnCallback(static function (string $message): void {
                self::assertContains($message, ['Outbox email sent', 'Outbox processing completed']);
            })
        ;

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }

    public function testInvokeHandlesFailedEmailsAndIncrementsAttempts(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-3',
            emailType: 'welcome',
            recipientEmail: 'bob@example.com',
            recipientName: 'Bob',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->willReturn([$email])
        ;

        $this->sender
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('SMTP connection failed'))
        ;

        $this->outbox
            ->expects(self::once())
            ->method('update')
            ->willReturnCallback(static function (EmailOutbox $email): void {
                // Verify email is marked as failed but still pending
                self::assertSame(EmailStatus::PENDING, $email->getStatus());
                self::assertSame(1, $email->getAttempts());
                self::assertSame('SMTP connection failed', $email->getLastError());
                self::assertTrue($email->canRetry());
            })
        ;

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with('Outbox email failed, will retry', self::callback(static function (array $context): bool {
                return isset($context['attempts']) && 1 === $context['attempts'];
            }))
        ;

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }

    public function testInvokeMarksPermanentlyFailedEmailsAfterMaxAttempts(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-4',
            emailType: 'welcome',
            recipientEmail: 'charlie@example.com',
            recipientName: 'Charlie',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        // Simulate 4 previous failures
        $email->markAsFailed('Error 1');
        $email->markAsFailed('Error 2');
        $email->markAsFailed('Error 3');
        $email->markAsFailed('Error 4');

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->willReturn([$email])
        ;

        $this->sender
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('Final error'))
        ;

        $this->outbox
            ->expects(self::once())
            ->method('update')
            ->willReturnCallback(static function (EmailOutbox $email): void {
                // Verify email is permanently failed
                self::assertSame(EmailStatus::FAILED, $email->getStatus());
                self::assertSame(5, $email->getAttempts());
                self::assertFalse($email->canRetry());
            })
        ;

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with('Outbox email permanently failed', self::callback(static function (array $context): bool {
                return isset($context['attempts']) && 5 === $context['attempts'];
            }))
        ;

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }

    public function testInvokeProcessesMixedSuccessAndFailureEmails(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $successEmail = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-5',
            emailType: 'welcome',
            recipientEmail: 'success@example.com',
            recipientName: 'Success',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $failEmail = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-6',
            emailType: 'welcome',
            recipientEmail: 'fail@example.com',
            recipientName: 'Fail',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->willReturn([$successEmail, $failEmail])
        ;

        $this->sender
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(static function (string $email): void {
                if ('fail@example.com' === $email) {
                    throw new \Exception('Send failed');
                }
            })
        ;

        $this->outbox
            ->expects(self::exactly(2))
            ->method('update')
        ;

        $this->logger
            ->expects(self::exactly(2))
            ->method('info')
        ;

        $this->logger
            ->expects(self::once())
            ->method('warning')
        ;

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }

    public function testInvokeLogsSummaryWithCorrectCounts(): void
    {
        // Arrange
        $command = new ProcessEmailOutbox();

        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-7',
            emailType: 'welcome',
            recipientEmail: 'test1@example.com',
            recipientName: 'Test1',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: 'event-8',
            emailType: 'welcome',
            recipientEmail: 'test2@example.com',
            recipientName: 'Test2',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome'
        );

        $this->outbox
            ->expects(self::once())
            ->method('findPending')
            ->willReturn([$email1, $email2])
        ;

        $this->sender
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                null, // Success
                self::throwException(new \Exception('Error')) // Failure
            )
        ;

        $this->outbox->method('update');

        $this->logger
            ->expects(self::atLeastOnce())
            ->method('info')
            ->willReturnCallback(static function (string $message, array $context): void {
                if ('Outbox processing completed' === $message) {
                    self::assertSame(2, $context['total_processed']);
                    self::assertSame(1, $context['sent']);
                    self::assertSame(1, $context['failed_retry']);
                    self::assertSame(0, $context['failed_permanent']);
                }
            })
        ;

        // Act
        ($this->handler)($command);

        // Assert - expectations verified by mock
    }
}
