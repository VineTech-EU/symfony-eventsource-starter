<?php

declare(strict_types=1);

namespace App\Tests\Modules\Notification\Adapters\Service;

use App\Modules\Notification\Adapters\Service\EmailSender;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @covers \App\Modules\Notification\Adapters\Service\EmailSender
 *
 * @internal
 */
final class EmailSenderUnitTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private EmailSender $sender;
    private string $fromEmail = 'noreply@example.com';
    private string $fromName = 'Example App';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->sender = new EmailSender(
            $this->mailer,
            $this->fromEmail,
            $this->fromName
        );
    }

    public function testSendCreatesEmailWithCorrectRecipientAndSubject(): void
    {
        // Arrange
        $recipientEmail = 'john@example.com';
        $subject = 'Welcome!';
        $rendered = new RenderedEmail(
            html: '<html>Welcome</html>',
            text: 'Welcome'
        );

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($recipientEmail, $subject): bool {
                $toAddresses = $email->getTo();
                self::assertCount(1, $toAddresses);
                self::assertSame($recipientEmail, $toAddresses[0]->getAddress());
                self::assertSame($subject, $email->getSubject());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }

    public function testSendSetsCorrectFromAddress(): void
    {
        // Arrange
        $recipientEmail = 'jane@example.com';
        $subject = 'Test';
        $rendered = new RenderedEmail(html: '<html>Test</html>', text: 'Test');

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(function (Email $email): bool {
                $fromAddresses = $email->getFrom();
                self::assertCount(1, $fromAddresses);
                self::assertSame($this->fromEmail, $fromAddresses[0]->getAddress());
                self::assertSame($this->fromName, $fromAddresses[0]->getName());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }

    public function testSendIncludesBothHtmlAndTextVersions(): void
    {
        // Arrange
        $recipientEmail = 'bob@example.com';
        $subject = 'Multi-part email';
        $rendered = new RenderedEmail(
            html: '<html><body><h1>Hello</h1></body></html>',
            text: 'Hello'
        );

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($rendered): bool {
                // Verify HTML body
                self::assertSame($rendered->html, $email->getHtmlBody());

                // Verify text body
                self::assertSame($rendered->text, $email->getTextBody());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }

    public function testSendThrowsExceptionWhenMailerFails(): void
    {
        // Arrange
        $recipientEmail = 'fail@example.com';
        $subject = 'Test';
        $rendered = new RenderedEmail(html: '<html>Test</html>', text: 'Test');

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('SMTP connection failed'))
        ;

        // Assert
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('SMTP connection failed');

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);
    }

    public function testSendWithEmptyTextBodyStillSendsEmail(): void
    {
        // Arrange
        $recipientEmail = 'alice@example.com';
        $subject = 'HTML only';
        $rendered = new RenderedEmail(
            html: '<html>HTML only</html>',
            text: '' // Empty text body
        );

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($rendered): bool {
                self::assertSame($rendered->html, $email->getHtmlBody());
                self::assertSame('', $email->getTextBody());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }

    public function testSendWithSpecialCharactersInSubject(): void
    {
        // Arrange
        $recipientEmail = 'test@example.com';
        $subject = 'Test: Résumé & "Quotes" <Tags>';
        $rendered = new RenderedEmail(html: '<html>Test</html>', text: 'Test');

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($subject): bool {
                // Symfony Email handles encoding automatically
                self::assertSame($subject, $email->getSubject());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }

    public function testSendWithInternationalEmail(): void
    {
        // Arrange
        $recipientEmail = 'user@例え.jp'; // International domain
        $subject = 'こんにちは'; // Japanese subject
        $rendered = new RenderedEmail(
            html: '<html>こんにちは</html>',
            text: 'こんにちは'
        );

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email) use ($recipientEmail, $subject): bool {
                $toAddresses = $email->getTo();
                self::assertSame($recipientEmail, $toAddresses[0]->getAddress());
                self::assertSame($subject, $email->getSubject());

                return true;
            }))
        ;

        // Act
        $this->sender->send($recipientEmail, $subject, $rendered);

        // Assert - expectations verified by mock
    }
}
