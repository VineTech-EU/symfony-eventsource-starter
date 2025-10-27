<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Application\EventHandler\PublishUserEmailChangedIntegrationEvent;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Modules\User\PublicApi\Event\UserEmailWasChangedIntegrationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \App\Modules\User\Application\EventHandler\PublishUserEmailChangedIntegrationEvent
 *
 * @internal
 */
final class PublishUserEmailChangedIntegrationEventUnitTest extends TestCase
{
    private MessageBusInterface&MockObject $eventBus;
    private PublishUserEmailChangedIntegrationEvent $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new PublishUserEmailChangedIntegrationEvent($this->eventBus);
    }

    public function testInvokeDispatchesIntegrationEventWithCorrectData(): void
    {
        // Arrange
        $domainEvent = new UserEmailChanged(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com',
        );

        $this->eventBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function ($event): bool {
                if (!$event instanceof UserEmailWasChangedIntegrationEvent) {
                    return false;
                }

                return $event->userId === '550e8400-e29b-41d4-a716-446655440000'
                    && $event->oldEmail === 'old@example.com'
                    && $event->newEmail === 'new@example.com';
            }))
            ->willReturn(new Envelope(new \stdClass()))
        ;

        // Act
        ($this->handler)($domainEvent);

        // Assert - expectations verified by mock
    }
}
