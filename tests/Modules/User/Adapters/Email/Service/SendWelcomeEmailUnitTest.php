<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Email\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use App\Modules\User\Adapters\Email\Service\SendWelcomeEmail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Modules\User\Adapters\Email\Service\SendWelcomeEmail
 *
 * @internal
 */
final class SendWelcomeEmailUnitTest extends TestCase
{
    private EmailOutboxRepositoryInterface&MockObject $outbox;
    private EmailTemplateRenderer&MockObject $renderer;
    private MockObject&TranslatorInterface $translator;
    private SendWelcomeEmail $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outbox = $this->createMock(EmailOutboxRepositoryInterface::class);
        $this->renderer = $this->createMock(EmailTemplateRenderer::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->service = new SendWelcomeEmail(
            $this->outbox,
            $this->renderer,
            $this->translator
        );
    }

    public function testInvokeSendsWelcomeEmailWithCorrectParameters(): void
    {
        // Arrange
        $recipientEmail = 'john.doe@example.com';
        $recipientName = 'John Doe';
        $eventId = 'event-123';
        $subject = 'Welcome to our platform!';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Welcome John Doe!</body></html>',
            text: 'Welcome John Doe!'
        );

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('email.welcome.header', [], 'emails')
            ->willReturn($subject)
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with('@user_emails/user/welcome.html.twig', ['name' => $recipientName])
            ->willReturn($renderedEmail)
        ;

        // Key assertion: email saved to outbox with correct parameters
        $this->outbox
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (EmailOutbox $email) use ($recipientEmail, $recipientName, $eventId, $subject, $renderedEmail): bool {
                return $email->getEventId() === $eventId
                    && $email->getEmailType() === 'welcome'
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
        $recipientEmail = 'alice@example.com';
        $recipientName = 'Alice Smith';
        $eventId = 'event-456';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Welcome</body></html>',
            text: 'Welcome'
        );

        $this->translator
            ->method('trans')
            ->willReturn('Welcome')
        ;

        $this->renderer
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        // Key assertion: verify recipient email
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
        $recipientName = 'Test User With Special Chars: <>&"';
        $eventId = 'event-789';

        $renderedEmail = new RenderedEmail(html: '<html></html>', text: 'text');

        $this->translator->method('trans')->willReturn('Subject');

        // Key assertion: verify template context includes special characters
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/welcome.html.twig',
                ['name' => $recipientName]
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
        $renderedEmail = new RenderedEmail(html: '<html></html>', text: 'text');

        // Key assertion: verify translation key
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('email.welcome.header', [], 'emails')
            ->willReturn('Subject')
        ;

        $this->renderer->method('render')->willReturn($renderedEmail);
        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)('test@example.com', 'Test', 'event-id');
    }

    public function testInvokeUsesCorrectTwigTemplate(): void
    {
        // Arrange
        $renderedEmail = new RenderedEmail(html: '<html></html>', text: 'text');

        $this->translator->method('trans')->willReturn('Subject');

        // Key assertion: verify Twig template path
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                self::equalTo('@user_emails/user/welcome.html.twig'),
                self::anything()
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)('test@example.com', 'Test', 'event-id');
    }
}
