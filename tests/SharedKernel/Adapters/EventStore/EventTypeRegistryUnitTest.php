<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\EventStore;

use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\SharedKernel\Adapters\EventStore\EventTypeRegistry;
use App\Tests\Support\FakeDuplicateUserCreated;
use App\Tests\Support\FakeOrderCreated;
use App\Tests\Support\FakeUserCreatedV2;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EventTypeRegistry::class)]
final class EventTypeRegistryUnitTest extends TestCase
{
    private EventTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new EventTypeRegistry();
    }

    #[Test]
    public function registerAddsEventToRegistry(): void
    {
        $this->registry->register('user.created', UserCreated::class);

        self::assertTrue($this->registry->has('user.created'));
        self::assertSame(
            UserCreated::class,
            $this->registry->getEventClass('user.created')
        );
    }

    #[Test]
    public function registerMultipleEventsWorks(): void
    {
        $this->registry->register('user.created', UserCreated::class);
        $this->registry->register('user.email_changed', UserEmailChanged::class);
        $this->registry->register('order.created', FakeOrderCreated::class);

        self::assertTrue($this->registry->has('user.created'));
        self::assertTrue($this->registry->has('user.email_changed'));
        self::assertTrue($this->registry->has('order.created'));

        $registeredEvents = $this->registry->getRegisteredEventNames();
        self::assertCount(3, $registeredEvents);
        self::assertContains('user.created', $registeredEvents);
        self::assertContains('user.email_changed', $registeredEvents);
        self::assertContains('order.created', $registeredEvents);
    }

    #[Test]
    public function registerThrowsExceptionWhenDuplicateEventName(): void
    {
        $this->registry->register('user.created', UserCreated::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Event name "user.created" is already registered to "' . UserCreated::class . '", '
            . 'cannot register "' . FakeDuplicateUserCreated::class . '"'
        );

        $this->registry->register('user.created', FakeDuplicateUserCreated::class);
    }

    #[Test]
    public function getEventClassThrowsExceptionWhenEventNotRegistered(): void
    {
        $this->registry->register('user.created', UserCreated::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown event name: "user.deleted". Registered events: user.created');

        $this->registry->getEventClass('user.deleted');
    }

    #[Test]
    public function getEventClassThrowsExceptionWithEmptyRegistryMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown event name: "user.created". Registered events: ');

        $this->registry->getEventClass('user.created');
    }

    #[Test]
    public function hasReturnsFalseForUnregisteredEvent(): void
    {
        $this->registry->register('user.created', UserCreated::class);

        self::assertTrue($this->registry->has('user.created'));
        self::assertFalse($this->registry->has('user.deleted'));
        self::assertFalse($this->registry->has('order.created'));
    }

    #[Test]
    public function getRegisteredEventNamesReturnsEmptyArrayInitially(): void
    {
        self::assertSame([], $this->registry->getRegisteredEventNames());
    }

    #[Test]
    public function getRegisteredEventNamesReturnsAllEventNames(): void
    {
        $this->registry->register('user.created', UserCreated::class);
        $this->registry->register('user.email_changed', UserEmailChanged::class);
        $this->registry->register('order.created', FakeOrderCreated::class);

        $eventNames = $this->registry->getRegisteredEventNames();

        self::assertCount(3, $eventNames);
        self::assertContains('user.created', $eventNames);
        self::assertContains('user.email_changed', $eventNames);
        self::assertContains('order.created', $eventNames);
    }

    #[Test]
    public function getEventClassReturnsCorrectClassForRegisteredEvent(): void
    {
        $this->registry->register('user.created', UserCreated::class);
        $this->registry->register('order.created', FakeOrderCreated::class);

        self::assertSame(
            UserCreated::class,
            $this->registry->getEventClass('user.created')
        );
        self::assertSame(
            FakeOrderCreated::class,
            $this->registry->getEventClass('order.created')
        );
    }

    #[Test]
    public function registryHandlesCaseSensitiveEventNames(): void
    {
        $this->registry->register('user.created', UserCreated::class);
        $this->registry->register('User.Created', FakeUserCreatedV2::class);

        self::assertTrue($this->registry->has('user.created'));
        self::assertTrue($this->registry->has('User.Created'));
        self::assertFalse($this->registry->has('USER.CREATED'));

        self::assertSame(
            UserCreated::class,
            $this->registry->getEventClass('user.created')
        );
        self::assertSame(
            FakeUserCreatedV2::class,
            $this->registry->getEventClass('User.Created')
        );
    }
}
