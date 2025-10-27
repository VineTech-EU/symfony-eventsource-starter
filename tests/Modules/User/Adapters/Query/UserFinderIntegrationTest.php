<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Query;

use App\Modules\User\Adapters\Persistence\Projection\UserFinder;
use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;
use App\Modules\User\Adapters\Persistence\Projection\UserReadModelRepository;
use App\Tests\Support\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Integration tests for UserFinder.
 * Tests query side (read model) with real database.
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\Persistence\Projection\UserFinder
 */
final class UserFinderIntegrationTest extends IntegrationTestCase
{
    private EntityManagerInterface $entityManager;
    private UserFinder $userFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(UserReadModelRepository::class);
        $this->userFinder = new UserFinder($repository);
    }

    public function testFindByIdReturnsUserDtoWhenUserExists(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $readModel = new UserReadModel(
            id: $userId,
            email: 'john@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'approved',
            createdAt: new \DateTimeImmutable()
        );

        $this->entityManager->persist($readModel);
        $this->entityManager->flush();

        // Act
        $result = $this->userFinder->findById($userId);

        // Assert
        self::assertNotNull($result);
        self::assertSame($userId, $result->id);
        self::assertSame('john@example.com', $result->email);
        self::assertSame('John Doe', $result->name);
        self::assertSame('approved', $result->status);
        self::assertSame(['ROLE_USER'], $result->roles);
    }

    public function testFindByIdReturnsNullWhenUserDoesNotExist(): void
    {
        // Act
        $result = $this->userFinder->findById('non-existent-id');

        // Assert
        self::assertNull($result);
    }

    public function testFindByEmailReturnsUserDtoWhenUserExists(): void
    {
        // Arrange
        $readModel = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440000',
            email: 'findme@example.com',
            name: 'Find Me',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable()
        );

        $this->entityManager->persist($readModel);
        $this->entityManager->flush();

        // Act
        $result = $this->userFinder->findByEmail('findme@example.com');

        // Assert
        self::assertNotNull($result);
        self::assertSame('findme@example.com', $result->email);
        self::assertSame('Find Me', $result->name);
    }

    public function testFindByEmailReturnsNullWhenUserDoesNotExist(): void
    {
        // Act
        $result = $this->userFinder->findByEmail('nonexistent@example.com');

        // Assert
        self::assertNull($result);
    }

    public function testFindAllReturnsAllUsers(): void
    {
        // Arrange
        $user1 = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440001',
            email: 'user1@example.com',
            name: 'User 1',
            roles: ['ROLE_USER'],
            status: 'approved',
            createdAt: new \DateTimeImmutable()
        );

        $user2 = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440002',
            email: 'user2@example.com',
            name: 'User 2',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable()
        );

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        // Act
        $result = $this->userFinder->findAll();

        // Assert
        self::assertCount(2, $result);
        self::assertSame('user1@example.com', $result[0]->email);
        self::assertSame('user2@example.com', $result[1]->email);
    }

    public function testFindAllReturnsEmptyArrayWhenNoUsersExist(): void
    {
        // Act
        $result = $this->userFinder->findAll();

        // Assert
        self::assertEmpty($result);
    }

    public function testFindPaginatedReturnsCorrectPage(): void
    {
        // Arrange - Create 25 users
        for ($i = 1; $i <= 25; ++$i) {
            $user = new UserReadModel(
                id: \sprintf('550e8400-e29b-41d4-a716-44665544%04d', $i),
                email: \sprintf('user%d@example.com', $i),
                name: \sprintf('User %d', $i),
                roles: ['ROLE_USER'],
                status: 'approved',
                createdAt: new \DateTimeImmutable()
            );
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        // Act - Get page 1 (first 20)
        $page1 = $this->userFinder->findPaginated(page: 1, limit: 20);

        // Assert
        self::assertCount(20, $page1->items);
        self::assertSame(25, $page1->total);
        self::assertSame(1, $page1->page);
        self::assertSame(20, $page1->limit);
        self::assertSame(2, $page1->pages);
    }

    public function testFindPaginatedReturnsSecondPage(): void
    {
        // Arrange - Create 25 users
        for ($i = 1; $i <= 25; ++$i) {
            $user = new UserReadModel(
                id: \sprintf('550e8400-e29b-41d4-a716-44665544%04d', $i),
                email: \sprintf('user%d@example.com', $i),
                name: \sprintf('User %d', $i),
                roles: ['ROLE_USER'],
                status: 'approved',
                createdAt: new \DateTimeImmutable()
            );
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        // Act - Get page 2 (last 5)
        $page2 = $this->userFinder->findPaginated(page: 2, limit: 20);

        // Assert
        self::assertCount(5, $page2->items);
        self::assertSame(25, $page2->total);
        self::assertSame(2, $page2->page);
    }

    public function testFindPaginatedWithCustomLimit(): void
    {
        // Arrange - Create 15 users
        for ($i = 1; $i <= 15; ++$i) {
            $user = new UserReadModel(
                id: \sprintf('550e8400-e29b-41d4-a716-44665544%04d', $i),
                email: \sprintf('user%d@example.com', $i),
                name: \sprintf('User %d', $i),
                roles: ['ROLE_USER'],
                status: 'approved',
                createdAt: new \DateTimeImmutable()
            );
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        // Act - Get with limit of 10
        $result = $this->userFinder->findPaginated(page: 1, limit: 10);

        // Assert
        self::assertCount(10, $result->items);
        self::assertSame(15, $result->total);
        self::assertSame(10, $result->limit);
        self::assertSame(2, $result->pages);
    }

    public function testFindByIdReturnsCompleteUserDTO(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $readModel = new UserReadModel(
            id: $userId,
            email: 'complete@example.com',
            name: 'Complete User',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
            status: 'approved',
            createdAt: new \DateTimeImmutable('2024-01-01 10:00:00')
        );

        $this->entityManager->persist($readModel);
        $this->entityManager->flush();

        // Act
        $result = $this->userFinder->findById($userId);

        // Assert - Verify all fields
        self::assertNotNull($result);
        self::assertSame($userId, $result->id);
        self::assertSame('complete@example.com', $result->email);
        self::assertSame('Complete User', $result->name);
        self::assertSame('approved', $result->status);
        self::assertSame('Approved', $result->statusLabel);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result->roles);
        self::assertNotEmpty($result->createdAt); // Timestamp is string in DTO
        self::assertNotEmpty($result->updatedAt); // Timestamp is string in DTO
    }

    public function testFindByIdWithDifferentStatuses(): void
    {
        // Arrange
        $pending = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440001',
            email: 'pending@example.com',
            name: 'Pending User',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable()
        );

        $approved = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440002',
            email: 'approved@example.com',
            name: 'Approved User',
            roles: ['ROLE_USER'],
            status: 'approved',
            createdAt: new \DateTimeImmutable()
        );

        $rejected = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440003',
            email: 'rejected@example.com',
            name: 'Rejected User',
            roles: ['ROLE_USER'],
            status: 'rejected',
            createdAt: new \DateTimeImmutable()
        );

        $this->entityManager->persist($pending);
        $this->entityManager->persist($approved);
        $this->entityManager->persist($rejected);
        $this->entityManager->flush();

        // Act & Assert
        $pendingResult = $this->userFinder->findById('550e8400-e29b-41d4-a716-446655440001');
        self::assertNotNull($pendingResult);
        self::assertSame('pending', $pendingResult->status);
        self::assertSame('Pending Approval', $pendingResult->statusLabel);

        $approvedResult = $this->userFinder->findById('550e8400-e29b-41d4-a716-446655440002');
        self::assertNotNull($approvedResult);
        self::assertSame('approved', $approvedResult->status);
        self::assertSame('Approved', $approvedResult->statusLabel);

        $rejectedResult = $this->userFinder->findById('550e8400-e29b-41d4-a716-446655440003');
        self::assertNotNull($rejectedResult);
        self::assertSame('rejected', $rejectedResult->status);
        self::assertSame('Rejected', $rejectedResult->statusLabel);
    }
}
