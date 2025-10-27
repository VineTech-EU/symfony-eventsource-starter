<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Application\EventHandler\PublishUserApprovedIntegrationEvent;
use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\PublicApi\Event\UserWasApprovedIntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \App\Modules\User\Application\EventHandler\PublishUserApprovedIntegrationEvent
 *
 * @internal
 */
final class PublishUserApprovedIntegrationEventUnitTest extends TestCase
{
    private MessageBusInterface&MockObject $eventBus;
    private PublishUserApprovedIntegrationEvent $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new PublishUserApprovedIntegrationEvent($this->eventBus);
    }

    public function testInvokeDispatchesIntegrationEventWithCorrectData(): void
    {
        // Arrange
        $domainEvent = new UserApproved(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
        );

        $this->eventBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function ($event): bool {
                if (!$event instanceof UserWasApprovedIntegrationEvent) {
                    return false;
                }

                return $event->userId === '550e8400-e29b-41d4-a716-446655440000'
                    && $event->email === 'john.doe@example.com'
                    && $event->name === 'John Doe';
            }))
            ->willReturn(new Envelope(new \stdClass()))
        ;

        // Act
        ($this->handler)($domainEvent);

        // Assert - expectations verified by mock
    }
}
