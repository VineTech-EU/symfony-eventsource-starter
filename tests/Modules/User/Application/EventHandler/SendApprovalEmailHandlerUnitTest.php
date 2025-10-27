<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Adapters\Email\Service\SendApprovalConfirmationEmail;
use App\Modules\User\Application\EventHandler\SendApprovalEmailHandler;
use App\Modules\User\Domain\Event\UserApproved;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Application\EventHandler\SendApprovalEmailHandler
 *
 * @internal
 */
final class SendApprovalEmailHandlerUnitTest extends TestCase
{
    private MockObject&SendApprovalConfirmationEmail $sendApprovalEmail;
    private SendApprovalEmailHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sendApprovalEmail = $this->createMock(SendApprovalConfirmationEmail::class);
        $this->handler = new SendApprovalEmailHandler($this->sendApprovalEmail);
    }

    public function testInvokeSendsApprovalConfirmationEmail(): void
    {
        // Arrange
        $event = new UserApproved(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
        );

        $this->sendApprovalEmail
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                self::equalTo('john.doe@example.com'),
                self::equalTo('John Doe'),
            )
        ;

        // Act
        ($this->handler)($event);

        // Assert - expectations verified by mock
    }
}
