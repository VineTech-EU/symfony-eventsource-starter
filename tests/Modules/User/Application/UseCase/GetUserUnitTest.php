<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Application\UseCase;

use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\UserFinderInterface;
use App\Modules\User\Application\UseCase\GetUser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GetUser use case (Query).
 * Tests reading from projections via Finder.
 *
 * @internal
 *
 * @covers \App\Modules\User\Application\UseCase\GetUser
 */
final class GetUserUnitTest extends TestCase
{
    public function testGetUserReturnsUserDTO(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';
        $expectedDTO = new UserDTO(
            id: $userId,
            email: 'john@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'approved',
            statusLabel: 'Approved',
            createdAt: '2024-01-01 10:00:00',
            updatedAt: '2024-01-01 10:00:00'
        );

        $finder = $this->createMock(UserFinderInterface::class);
        $finder->expects(self::once())
            ->method('findById')
            ->with($userId)
            ->willReturn($expectedDTO)
        ;

        $useCase = new GetUser($finder);

        // Act
        $result = $useCase->execute($userId);

        // Assert
        self::assertSame($expectedDTO, $result);
        self::assertSame($userId, $result->id);
        self::assertSame('john@example.com', $result->email);
        self::assertSame('John Doe', $result->name);
        self::assertSame('approved', $result->status);
    }

    public function testGetNonExistentUserReturnsNull(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $finder = $this->createMock(UserFinderInterface::class);
        $finder->expects(self::once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null)
        ;

        $useCase = new GetUser($finder);

        // Act
        $result = $useCase->execute($userId);

        // Assert
        self::assertNull($result);
    }

    public function testGetUserDelegatestoFinder(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $finder = $this->createMock(UserFinderInterface::class);
        $finder->expects(self::once())
            ->method('findById')
            ->with($userId)
        ;

        $useCase = new GetUser($finder);

        // Act
        $useCase->execute($userId);

        // Assert - Verified by mock expectations
    }

    public function testGetUserReturnsCompleteUserData(): void
    {
        // Arrange
        $userId = '550e8400-e29b-41d4-a716-446655440000';

        $userDTO = new UserDTO(
            id: $userId,
            email: 'complete@example.com',
            name: 'Complete User',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
            status: 'pending',
            statusLabel: 'Pending Approval',
            createdAt: '2024-01-01 10:00:00',
            updatedAt: '2024-01-02 15:30:00'
        );

        $finder = $this->createMock(UserFinderInterface::class);
        $finder->method('findById')->willReturn($userDTO);

        $useCase = new GetUser($finder);

        // Act
        $result = $useCase->execute($userId);

        // Assert - Verify all fields
        self::assertNotNull($result);
        self::assertSame($userId, $result->id);
        self::assertSame('complete@example.com', $result->email);
        self::assertSame('Complete User', $result->name);
        self::assertSame('pending', $result->status);
        self::assertSame('Pending Approval', $result->statusLabel);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result->roles);
        self::assertSame('2024-01-01 10:00:00', $result->createdAt);
        self::assertSame('2024-01-02 15:30:00', $result->updatedAt);
    }
}
