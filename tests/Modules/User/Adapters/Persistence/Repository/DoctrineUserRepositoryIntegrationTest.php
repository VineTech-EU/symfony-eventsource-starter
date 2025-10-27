<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Persistence\Repository;

use App\Modules\User\Domain\Exception\UserNotFoundException;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\Email;
use App\Modules\User\Domain\ValueObject\UserId;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use App\Tests\Fixtures\Modules\User\Factory\UserFixtureFactory;
use App\Tests\Support\IntegrationTestCase;

/**
 * Integration test for User repository.
 * Tests the repository implementation with real database.
 *
 * @group functional
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\Persistence\Repository\DoctrineUserRepository
 */
final class DoctrineUserRepositoryIntegrationTest extends IntegrationTestCase
{
    private UserRepositoryInterface $repository;
    private UserFixtureFactory $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(UserRepositoryInterface::class);
        $this->users = self::getContainer()->get(UserFixtureFactory::class);
    }

    public function testSaveAndGetUser(): void
    {
        // Arrange
        $user = $this->users->create(static function ($b) {
            $b->withName('John Doe');
            $b->withEmail('john@example.com');
        });

        // Act
        $foundUser = $this->repository->get($user->getUserId());

        // Assert
        self::assertSame($user->getId(), $foundUser->getId());
        self::assertSame('John Doe', $foundUser->getName());
        self::assertSame('john@example.com', $foundUser->getEmail()->toString());
    }

    public function testGetWithNonExistentUserThrowsException(): void
    {
        // Arrange
        $nonExistentId = UserId::fromString(SymfonyUuid::generate()->toString());

        // Act & Assert
        $this->expectException(UserNotFoundException::class);

        $this->repository->get($nonExistentId);
    }

    public function testSaveMultipleUsersAndRetrieveThem(): void
    {
        // Arrange & Act
        $users = $this->users->createMany(5);

        // Assert - all users can be retrieved
        foreach ($users as $user) {
            $foundUser = $this->repository->get($user->getUserId());
            self::assertSame($user->getId(), $foundUser->getId());
        }
    }

    public function testApproveUserAndPersistStatus(): void
    {
        // Arrange
        $user = $this->users->createPending();

        // Act - Approve the user
        $user->approve();
        $this->repository->save($user);

        // Assert - Status is persisted
        $foundUser = $this->repository->get($user->getUserId());
        self::assertTrue($foundUser->isApproved());
        self::assertFalse($foundUser->isPending());
    }

    public function testChangeEmailAndPersist(): void
    {
        // Arrange
        $user = $this->users->create(static function ($b) {
            $b->withEmail('old@example.com');
        });

        // Act - Change email
        $newEmail = Email::fromString('new@example.com');
        $user->changeEmail($newEmail);
        $this->repository->save($user);

        // Assert - Email change is persisted
        $foundUser = $this->repository->get($user->getUserId());
        self::assertSame('new@example.com', $foundUser->getEmail()->toString());
    }
}
