<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Application\EventHandler\PublishUserCreatedIntegrationEvent;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\PublicApi\Event\UserWasCreatedIntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \App\Modules\User\Application\EventHandler\PublishUserCreatedIntegrationEvent
 *
 * @internal
 */
final class PublishUserCreatedIntegrationEventUnitTest extends TestCase
{
    private MessageBusInterface&MockObject $eventBus;
    private PublishUserCreatedIntegrationEvent $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new PublishUserCreatedIntegrationEvent($this->eventBus);
    }

    public function testInvokeDispatchesIntegrationEventWithCorrectData(): void
    {
        // Arrange
        $domainEvent = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
        );

        $this->eventBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(UserWasCreatedIntegrationEvent::class))
            ->willReturnCallback(static function (UserWasCreatedIntegrationEvent $event) {
                // Verify event properties
                self::assertSame('550e8400-e29b-41d4-a716-446655440000', $event->userId);
                self::assertSame('john.doe@example.com', $event->email);
                self::assertSame('John Doe', $event->name);
                self::assertSame('ROLE_USER', $event->role);
                self::assertSame('pending', $event->status);
                self::assertInstanceOf(\DateTimeImmutable::class, $event->occurredOn);

                return new Envelope(new \stdClass());
            })
        ;

        // Act
        ($this->handler)($domainEvent);

        // Assert - expectations verified by mock callback
    }
}
