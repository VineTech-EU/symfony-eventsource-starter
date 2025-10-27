<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\EventHandler;

use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;
use App\Modules\User\Adapters\Persistence\Projection\UserReadModelRepository;
use App\Modules\User\Application\EventHandler\UserProjectionHandler;
use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Event\UserCreated;
use App\Modules\User\Domain\Event\UserEmailChanged;
use App\Tests\Support\IntegrationTestCase;

/**
 * Integration tests for UserProjectionHandler.
 * Tests that domain events properly update read model projections (CQRS) with real database.
 *
 * @internal
 *
 * @covers \App\Modules\User\Application\EventHandler\UserProjectionHandler
 */
final class UserProjectionHandlerIntegrationTest extends IntegrationTestCase
{
    private UserReadModelRepository $repository;
    private UserProjectionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(UserReadModelRepository::class);
        $this->handler = new UserProjectionHandler($this->repository);
    }

    public function testHandleUserCreatedCreatesReadModel(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $email = 'john@example.com';
        $name = 'John Doe';
        $roles = ['ROLE_USER'];
        $status = 'pending';

        $event = new UserCreated(
            userId: $userId,
            email: $email,
            name: $name,
            roles: $roles,
            status: $status
        );

        // Act
        $this->handler->handleUserCreated($event);

        // Assert
        $readModel = $this->repository->findById($userId);
        self::assertNotNull($readModel);
        self::assertSame($userId, $readModel->getId());
        self::assertSame($email, $readModel->getEmail());
        self::assertSame($name, $readModel->getName());
        self::assertSame('pending', $readModel->getStatusValue());
        self::assertSame(['ROLE_USER'], $readModel->getRoles());
    }

    public function testHandleUserCreatedWithMultipleRoles(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440001';
        $event = new UserCreated(
            userId: $userId,
            email: 'admin@example.com',
            name: 'Admin User',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
            status: 'approved'
        );

        // Act
        $this->handler->handleUserCreated($event);

        // Assert
        $readModel = $this->repository->findById($userId);
        self::assertNotNull($readModel);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $readModel->getRoles());
        self::assertSame('approved', $readModel->getStatusValue());
        self::assertTrue($readModel->isApproved());
    }

    public function testHandleUserEmailChangedUpdatesReadModel(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $oldEmail = 'old@example.com';
        $newEmail = 'new@example.com';

        // Create initial read model
        $readModel = new UserReadModel(
            id: $userId,
            email: $oldEmail,
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable()
        );
        $this->repository->save($readModel);

        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: $oldEmail,
            newEmail: $newEmail
        );

        // Act
        $this->handler->handleUserEmailChanged($event);

        // Assert
        $updatedReadModel = $this->repository->findById($userId);
        self::assertNotNull($updatedReadModel);
        self::assertSame($newEmail, $updatedReadModel->getEmail());
    }

    public function testHandleUserEmailChangedThrowsExceptionWhenReadModelNotFound(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com'
        );

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User read model not found');

        $this->handler->handleUserEmailChanged($event);
    }

    public function testHandleUserApprovedUpdatesReadModel(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        // Create initial read model with pending status
        $readModel = new UserReadModel(
            id: $userId,
            email: 'user@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable()
        );
        $this->repository->save($readModel);

        $event = new UserApproved(
            userId: $userId,
            email: 'user@example.com',
            name: 'John Doe'
        );

        // Act
        $this->handler->handleUserApproved($event);

        // Assert
        $updatedReadModel = $this->repository->findById($userId);
        self::assertNotNull($updatedReadModel);
        self::assertSame('approved', $updatedReadModel->getStatusValue());
        self::assertTrue($updatedReadModel->isApproved());
    }

    public function testHandleUserApprovedThrowsExceptionWhenReadModelNotFound(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $event = new UserApproved(
            userId: $userId,
            email: 'user@example.com',
            name: 'User Name'
        );

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User read model not found');

        $this->handler->handleUserApproved($event);
    }

    public function testHandleUserCreatedSetsTimestamps(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $before = new \DateTimeImmutable();

        $event = new UserCreated(
            userId: $userId,
            email: 'test@example.com',
            name: 'Test User',
            roles: ['ROLE_USER'],
            status: 'pending'
        );

        // Act
        $this->handler->handleUserCreated($event);

        $after = new \DateTimeImmutable();

        // Assert
        $readModel = $this->repository->findById($userId);
        self::assertNotNull($readModel);
        self::assertInstanceOf(\DateTimeImmutable::class, $readModel->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $readModel->getUpdatedAt());
        self::assertGreaterThanOrEqual($before, $readModel->getCreatedAt());
        self::assertLessThanOrEqual($after, $readModel->getCreatedAt());
    }

    public function testHandleUserEmailChangedUpdatesUpdatedAt(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');

        $readModel = new UserReadModel(
            id: $userId,
            email: 'old@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: $createdAt
        );
        $this->repository->save($readModel);

        $originalUpdatedAt = $readModel->getUpdatedAt();

        // Small delay to ensure timestamp changes
        usleep(1000);

        $event = new UserEmailChanged(
            userId: $userId,
            oldEmail: 'old@example.com',
            newEmail: 'new@example.com'
        );

        // Act
        $this->handler->handleUserEmailChanged($event);

        // Assert
        $updatedReadModel = $this->repository->findById($userId);
        self::assertNotNull($updatedReadModel);
        self::assertSame($createdAt, $updatedReadModel->getCreatedAt()); // Should not change
        self::assertGreaterThanOrEqual($originalUpdatedAt, $updatedReadModel->getUpdatedAt()); // Should be updated
    }
}
