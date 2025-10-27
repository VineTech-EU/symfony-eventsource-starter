<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Adapters\Email\Service\SendAdminNotification;
use App\Modules\User\Application\EventHandler\NotifyAdminsOfNewUserHandler;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;
use App\Modules\User\Application\Query\UserFinderInterface;
use App\Modules\User\Domain\Event\UserCreated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Application\EventHandler\NotifyAdminsOfNewUserHandler
 *
 * @internal
 */
final class NotifyAdminsOfNewUserHandlerUnitTest extends TestCase
{
    private MockObject&SendAdminNotification $sendAdminNotification;
    private MockObject&UserFinderInterface $userFinder;
    private NotifyAdminsOfNewUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sendAdminNotification = $this->createMock(SendAdminNotification::class);
        $this->userFinder = $this->createMock(UserFinderInterface::class);
        $this->handler = new NotifyAdminsOfNewUserHandler(
            $this->sendAdminNotification,
            $this->userFinder
        );
    }

    public function testInvokeNotifiesAllAdminsOfNewUser(): void
    {
        // Arrange
        $event = new UserCreated(
            userId: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
        );

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

        $this->userFinder
            ->expects(self::once())
            ->method('findAdmins')
            ->willReturn($admins)
        ;

        $this->sendAdminNotification
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                self::equalTo($admins),
                self::equalTo('john.doe@example.com'),
                self::equalTo('John Doe'),
            )
        ;

        // Act
        ($this->handler)($event);

        // Assert - expectations verified by mock
    }

    public function testInvokeHandlesEmptyAdminList(): void
    {
        // Arrange
        $event = new UserCreated(
            userId: '660e8400-e29b-41d4-a716-446655440001',
            email: 'jane.smith@example.com',
            name: 'Jane Smith',
            roles: ['ROLE_USER'],
            status: 'pending',
        );

        $this->userFinder
            ->expects(self::once())
            ->method('findAdmins')
            ->willReturn([])
        ;

        $this->sendAdminNotification
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                self::equalTo([]),
                self::equalTo('jane.smith@example.com'),
                self::equalTo('Jane Smith'),
            )
        ;

        // Act
        ($this->handler)($event);

        // Assert - expectations verified by mock
    }
}
