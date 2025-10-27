<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Email\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use App\Modules\User\Adapters\Email\Service\SendApprovalConfirmationEmail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Modules\User\Adapters\Email\Service\SendApprovalConfirmationEmail
 *
 * @internal
 */
final class SendApprovalConfirmationEmailUnitTest extends TestCase
{
    private EmailOutboxRepositoryInterface&MockObject $outbox;
    private EmailTemplateRenderer&MockObject $renderer;
    private MockObject&TranslatorInterface $translator;
    private SendApprovalConfirmationEmail $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outbox = $this->createMock(EmailOutboxRepositoryInterface::class);
        $this->renderer = $this->createMock(EmailTemplateRenderer::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->service = new SendApprovalConfirmationEmail(
            $this->outbox,
            $this->renderer,
            $this->translator
        );
    }

    public function testInvokeSendsApprovalEmailWithCorrectParameters(): void
    {
        // Arrange
        $recipientEmail = 'john.doe@example.com';
        $recipientName = 'John Doe';
        $eventId = 'event-123';
        $subject = 'Your account has been approved!';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Congratulations John Doe!</body></html>',
            text: 'Congratulations John Doe!'
        );

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('email.approval.header', [], 'emails')
            ->willReturn($subject)
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/approval_confirmation.html.twig',
                ['name' => $recipientName]
            )
            ->willReturn($renderedEmail)
        ;

        // Key assertion: email saved to outbox with correct parameters
        $this->outbox
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (EmailOutbox $email) use ($recipientEmail, $recipientName, $eventId, $subject, $renderedEmail): bool {
                return $email->getEventId() === $eventId
                    && $email->getEmailType() === 'approval_confirmation'
                    && $email->getRecipientEmail() === $recipientEmail
                    && $email->getRecipientName() === $recipientName
                    && $email->getSubject() === $subject
                    && $email->getHtmlBody() === $renderedEmail->html
                    && $email->getTextBody() === $renderedEmail->text;
            }))
        ;

        // Act
        ($this->service)($recipientEmail, $recipientName, $eventId);
    }

    public function testInvokeSendsEmailToDifferentRecipient(): void
    {
        // Arrange
        $recipientEmail = 'admin@example.com';
        $recipientName = 'Admin User';
        $eventId = 'event-456';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Approved!</body></html>',
            text: 'Approved!'
        );

        $this->translator
            ->method('trans')
            ->willReturn('Approval confirmed')
        ;

        $this->renderer
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        // Key assertion: verify recipient email and name
        $this->outbox
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (EmailOutbox $email): bool => $email->getRecipientEmail() === $recipientEmail && $email->getRecipientName() === $recipientName))
        ;

        // Act
        ($this->service)($recipientEmail, $recipientName, $eventId);
    }

    public function testInvokePassesCorrectTemplateContextToRenderer(): void
    {
        // Arrange
        $recipientEmail = 'test@example.com';
        $recipientName = 'User With Accents: éàù';
        $eventId = 'event-789';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator->method('trans')->willReturn('Approved');

        // Key assertion: verify template context includes accents
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/approval_confirmation.html.twig',
                ['name' => 'User With Accents: éàù']
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)($recipientEmail, $recipientName, $eventId);
    }

    public function testInvokeUsesCorrectTranslationKey(): void
    {
        // Arrange
        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        // Key assertion: verify correct translation key and domain
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with(
                self::equalTo('email.approval.header'),
                self::equalTo([]),
                self::equalTo('emails')
            )
            ->willReturn('Approved!')
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)('user@example.com', 'User', 'event-id');
    }

    public function testInvokeUsesCorrectTwigTemplate(): void
    {
        // Arrange
        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->willReturn('Approved')
        ;

        // Key assertion: verify correct Twig template path
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                self::equalTo('@user_emails/user/approval_confirmation.html.twig'),
                self::anything()
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)('user@example.com', 'User', 'event-id');
    }

    public function testInvokeHandlesEmptyRecipientName(): void
    {
        // Arrange
        $recipientEmail = 'user@example.com';
        $recipientName = '';
        $eventId = 'event-empty';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->willReturn('Approved')
        ;

        // Verify empty name is passed correctly
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/approval_confirmation.html.twig',
                ['name' => '']
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)($recipientEmail, $recipientName, $eventId);
    }
}
