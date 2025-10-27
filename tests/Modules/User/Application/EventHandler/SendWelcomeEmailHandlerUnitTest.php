<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Adapters\Email\Service\SendWelcomeEmail;
use App\Modules\User\Application\EventHandler\SendWelcomeEmailHandler;
use App\Modules\User\Domain\Event\UserCreated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Application\EventHandler\SendWelcomeEmailHandler
 *
 * @internal
 */
final class SendWelcomeEmailHandlerUnitTest extends TestCase
{
    private MockObject&SendWelcomeEmail $sendWelcomeEmail;
    private SendWelcomeEmailHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sendWelcomeEmail = $this->createMock(SendWelcomeEmail::class);
        $this->handler = new SendWelcomeEmailHandler($this->sendWelcomeEmail);
    }

    public function testInvokeSendsWelcomeEmailWithCorrectData(): void
    {
        // Arrange
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
        );

        $this->sendWelcomeEmail
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
