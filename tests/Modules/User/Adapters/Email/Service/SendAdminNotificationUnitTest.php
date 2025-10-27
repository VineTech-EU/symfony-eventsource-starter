<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Email\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\Repository\EmailOutboxRepositoryInterface;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use App\Modules\User\Adapters\Email\Service\SendAdminNotification;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Modules\User\Adapters\Email\Service\SendAdminNotification
 *
 * @internal
 */
final class SendAdminNotificationUnitTest extends TestCase
{
    private EmailOutboxRepositoryInterface&MockObject $outbox;
    private EmailTemplateRenderer&MockObject $renderer;
    private MockObject&TranslatorInterface $translator;
    private LoggerInterface&MockObject $logger;
    private SendAdminNotification $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outbox = $this->createMock(EmailOutboxRepositoryInterface::class);
        $this->renderer = $this->createMock(EmailTemplateRenderer::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new SendAdminNotification(
            $this->outbox,
            $this->renderer,
            $this->translator,
            $this->logger
        );
    }

    public function testInvokeSendsEmailToAllAdmins(): void
    {
        // Arrange
        $admin1 = new UserSummaryDTO(
            id: 'admin-1',
            email: 'admin1@example.com',
            name: 'Admin One'
        );
        $admin2 = new UserSummaryDTO(
            id: 'admin-2',
            email: 'admin2@example.com',
            name: 'Admin Two'
        );
        $admins = [$admin1, $admin2];

        $newUserEmail = 'newuser@example.com';
        $newUserName = 'New User';
        $eventId = 'event-123';

        $headerTranslation = 'New User Registration';
        $statusTranslation = 'Pending Approval';
        $expectedSubject = $headerTranslation . ' - ' . $statusTranslation;

        $renderedEmail = new RenderedEmail(
            html: '<html><body>New user registered</body></html>',
            text: 'New user registered'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $key) use ($headerTranslation, $statusTranslation): string {
                return match ($key) {
                    'email.admin.header' => $headerTranslation,
                    'email.admin.status_pending_value' => $statusTranslation,
                    default => throw new \RuntimeException('Unexpected translation key: ' . $key),
                };
            })
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/admin_notification.html.twig',
                [
                    'newUserEmail' => $newUserEmail,
                    'newUserName' => $newUserName,
                ]
            )
            ->willReturn($renderedEmail)
        ;

        // Key assertion: email saved to outbox for EACH admin
        $savedEmails = [];
        $this->outbox
            ->expects(self::exactly(2))
            ->method('save')
            ->with(self::callback(static function (EmailOutbox $email) use (&$savedEmails, $eventId, $expectedSubject, $renderedEmail): bool {
                $savedEmails[] = $email->getRecipientEmail();

                return $email->getEventId() === $eventId
                    && $email->getEmailType() === 'admin_notification'
                    && \in_array($email->getRecipientEmail(), ['admin1@example.com', 'admin2@example.com'], true)
                    && $email->getSubject() === $expectedSubject
                    && $email->getHtmlBody() === $renderedEmail->html
                    && $email->getTextBody() === $renderedEmail->text;
            }))
        ;

        // Act
        ($this->service)($admins, $newUserEmail, $newUserName, $eventId);

        // Assert - verify both admins received emails
        self::assertCount(2, $savedEmails);
        self::assertContains('admin1@example.com', $savedEmails);
        self::assertContains('admin2@example.com', $savedEmails);
    }

    public function testInvokeHandlesEmptyAdminList(): void
    {
        // Arrange
        $admins = [];
        $newUserEmail = 'newuser@example.com';
        $newUserName = 'New User';
        $eventId = 'event-empty';

        // Key assertion: no translations, no rendering, no emails saved
        $this->translator
            ->expects(self::never())
            ->method('trans')
        ;

        $this->renderer
            ->expects(self::never())
            ->method('render')
        ;

        $this->outbox
            ->expects(self::never())
            ->method('save')
        ;

        // Act
        ($this->service)($admins, $newUserEmail, $newUserName, $eventId);
    }

    public function testInvokeSendsEmailToSingleAdmin(): void
    {
        // Arrange
        $admin = new UserSummaryDTO(
            id: 'admin-1',
            email: 'superadmin@example.com',
            name: 'Super Admin'
        );
        $admins = [$admin];

        $newUserEmail = 'john@example.com';
        $newUserName = 'John Doe';
        $eventId = 'event-single';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Header', 'Status')
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        // Key assertion: email saved exactly once
        $this->outbox
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (EmailOutbox $email): bool => $email->getRecipientEmail() === 'superadmin@example.com'))
        ;

        // Act
        ($this->service)($admins, $newUserEmail, $newUserName, $eventId);
    }

    public function testInvokeUsesCorrectTranslationKeys(): void
    {
        // Arrange
        $admin = new UserSummaryDTO(
            id: 'admin-1',
            email: 'admin@example.com',
            name: 'Admin'
        );
        $admins = [$admin];
        $eventId = 'event-trans';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        // Key assertion: verify correct translation keys and domain
        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(static function (string $key, array $parameters, string $domain): string {
                self::assertSame('emails', $domain);
                self::assertSame([], $parameters);
                self::assertContains($key, ['email.admin.header', 'email.admin.status_pending_value']);

                return 'Translation';
            })
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)($admins, 'user@example.com', 'User', $eventId);
    }

    public function testInvokePassesCorrectTemplateContext(): void
    {
        // Arrange
        $admin = new UserSummaryDTO(
            id: 'admin-1',
            email: 'admin@example.com',
            name: 'Admin'
        );
        $admins = [$admin];

        $newUserEmail = 'special.user+test@example.com';
        $newUserName = 'User With Special Chars: <>&"';
        $eventId = 'event-special';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Header', 'Status')
        ;

        // Key assertion: verify template context includes special characters
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                '@user_emails/user/admin_notification.html.twig',
                [
                    'newUserEmail' => $newUserEmail,
                    'newUserName' => $newUserName,
                ]
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)($admins, $newUserEmail, $newUserName, $eventId);
    }

    public function testInvokeUsesCorrectTwigTemplate(): void
    {
        // Arrange
        $admin = new UserSummaryDTO(
            id: 'admin-1',
            email: 'admin@example.com',
            name: 'Admin'
        );
        $admins = [$admin];
        $eventId = 'event-template';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Header', 'Status')
        ;

        // Key assertion: verify correct Twig template path
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->with(
                self::equalTo('@user_emails/user/admin_notification.html.twig'),
                self::anything()
            )
            ->willReturn($renderedEmail)
        ;

        $this->outbox->expects(self::once())->method('save');

        // Act
        ($this->service)($admins, 'user@example.com', 'User', $eventId);
    }

    public function testInvokeConstructsSubjectCorrectly(): void
    {
        // Arrange
        $admin = new UserSummaryDTO(
            id: 'admin-1',
            email: 'admin@example.com',
            name: 'Admin'
        );
        $admins = [$admin];
        $eventId = 'event-subject';

        $headerTranslation = 'New User Registration';
        $statusTranslation = 'Pending Approval';
        $expectedSubject = $headerTranslation . ' - ' . $statusTranslation;

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls($headerTranslation, $statusTranslation)
        ;

        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        // Key assertion: verify subject is constructed as "header - status"
        $this->outbox
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (EmailOutbox $email): bool => $email->getSubject() === $expectedSubject))
        ;

        // Act
        ($this->service)($admins, 'user@example.com', 'User', $eventId);
    }

    public function testInvokeSendsToMultipleAdminsWithSameContent(): void
    {
        // Arrange
        $admin1 = new UserSummaryDTO(id: 'admin-1', email: 'admin1@example.com', name: 'Admin 1');
        $admin2 = new UserSummaryDTO(id: 'admin-2', email: 'admin2@example.com', name: 'Admin 2');
        $admin3 = new UserSummaryDTO(id: 'admin-3', email: 'admin3@example.com', name: 'Admin 3');
        $admins = [$admin1, $admin2, $admin3];
        $eventId = 'event-multiple';

        $renderedEmail = new RenderedEmail(
            html: '<html><body>Test</body></html>',
            text: 'Test'
        );

        $this->translator
            ->expects(self::exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Header', 'Status')
        ;

        // Key assertion: template rendered ONCE (not per admin)
        $this->renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn($renderedEmail)
        ;

        // Key assertion: email saved 3 times with same content
        $savedEmails = [];
        $this->outbox
            ->expects(self::exactly(3))
            ->method('save')
            ->with(self::callback(static function (EmailOutbox $email) use (&$savedEmails, $renderedEmail): bool {
                $savedEmails[] = $email->getRecipientEmail();

                return \in_array($email->getRecipientEmail(), ['admin1@example.com', 'admin2@example.com', 'admin3@example.com'], true)
                    && $email->getHtmlBody() === $renderedEmail->html
                    && $email->getTextBody() === $renderedEmail->text;
            }))
        ;

        // Act
        ($this->service)($admins, 'user@example.com', 'User', $eventId);

        // Assert
        self::assertCount(3, $savedEmails);
    }
}
