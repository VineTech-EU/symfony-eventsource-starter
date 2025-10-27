<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Persistence\Projection;

use App\Modules\User\Adapters\Persistence\Projection\UserMapper;
use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;
use App\Modules\User\Application\Query\DTO\UserDTO;
use App\Modules\User\Application\Query\DTO\UserSummaryDTO;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Adapters\Persistence\Projection\UserMapper
 *
 * @internal
 */
final class UserMapperUnitTest extends TestCase
{
    public function testToDTOMapsUserReadModelCorrectly(): void
    {
        // Arrange
        $model = new UserReadModel(
            id: '550e8400-e29b-41d4-a716-446655440000',
            email: 'john.doe@example.com',
            name: 'John Doe',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable('2024-01-01 10:00:00'),
        );

        // Act
        $dto = UserMapper::toDTO($model);

        // Assert
        self::assertInstanceOf(UserDTO::class, $dto);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $dto->id);
        self::assertSame('john.doe@example.com', $dto->email);
        self::assertSame('John Doe', $dto->name);
        self::assertSame(['ROLE_USER'], $dto->roles);
        self::assertSame('pending', $dto->status);
        self::assertSame('Pending Approval', $dto->statusLabel);
        self::assertSame('2024-01-01 10:00:00', $dto->createdAt);
        // updatedAt is set automatically to current time in constructor
        self::assertNotEmpty($dto->updatedAt);
    }

    public function testToDTOMapsApprovedUser(): void
    {
        // Arrange
        $model = new UserReadModel(
            id: '660e8400-e29b-41d4-a716-446655440001',
            email: 'admin@example.com',
            name: 'Admin User',
            roles: ['ROLE_USER', 'ROLE_ADMIN'],
            status: 'approved',
            createdAt: new \DateTimeImmutable('2024-02-01 10:00:00'),
        );

        // Act
        $dto = UserMapper::toDTO($model);

        // Assert
        self::assertSame('approved', $dto->status);
        self::assertSame('Approved', $dto->statusLabel);
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $dto->roles);
    }

    public function testToSummaryDTOMapsOnlyEssentialFields(): void
    {
        // Arrange
        $model = new UserReadModel(
            id: '770e8400-e29b-41d4-a716-446655440002',
            email: 'jane.smith@example.com',
            name: 'Jane Smith',
            roles: ['ROLE_USER'],
            status: 'approved',
            createdAt: new \DateTimeImmutable('2024-03-01 10:00:00'),
        );

        // Act
        $dto = UserMapper::toSummaryDTO($model);

        // Assert
        self::assertInstanceOf(UserSummaryDTO::class, $dto);
        self::assertSame('770e8400-e29b-41d4-a716-446655440002', $dto->id);
        self::assertSame('jane.smith@example.com', $dto->email);
        self::assertSame('Jane Smith', $dto->name);
    }

    public function testToDTOListMapsMultipleModels(): void
    {
        // Arrange
        $model1 = new UserReadModel(
            id: '880e8400-e29b-41d4-a716-446655440003',
            email: 'user1@example.com',
            name: 'User One',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable('2024-04-01 10:00:00'),
        );

        $model2 = new UserReadModel(
            id: '990e8400-e29b-41d4-a716-446655440004',
            email: 'user2@example.com',
            name: 'User Two',
            roles: ['ROLE_ADMIN'],
            status: 'approved',
            createdAt: new \DateTimeImmutable('2024-05-01 10:00:00'),
        );

        $models = [$model1, $model2];

        // Act
        $dtos = UserMapper::toDTOList($models);

        // Assert
        self::assertCount(2, $dtos);

        self::assertSame('880e8400-e29b-41d4-a716-446655440003', $dtos[0]->id);
        self::assertSame('user1@example.com', $dtos[0]->email);
        self::assertSame('pending', $dtos[0]->status);

        self::assertSame('990e8400-e29b-41d4-a716-446655440004', $dtos[1]->id);
        self::assertSame('user2@example.com', $dtos[1]->email);
        self::assertSame('approved', $dtos[1]->status);
    }

    public function testToDTOListReturnsEmptyArrayForEmptyInput(): void
    {
        // Arrange
        $models = [];

        // Act
        $dtos = UserMapper::toDTOList($models);

        // Assert
        self::assertSame([], $dtos);
    }

    public function testToSummaryDTOListMapsMultipleModels(): void
    {
        // Arrange
        $model1 = new UserReadModel(
            id: 'aa0e8400-e29b-41d4-a716-446655440005',
            email: 'summary1@example.com',
            name: 'Summary One',
            roles: ['ROLE_USER'],
            status: 'pending',
            createdAt: new \DateTimeImmutable('2024-06-01 10:00:00'),
        );

        $model2 = new UserReadModel(
            id: 'bb0e8400-e29b-41d4-a716-446655440006',
            email: 'summary2@example.com',
            name: 'Summary Two',
            roles: ['ROLE_USER'],
            status: 'approved',
            createdAt: new \DateTimeImmutable('2024-07-01 10:00:00'),
        );

        $models = [$model1, $model2];

        // Act
        $dtos = UserMapper::toSummaryDTOList($models);

        // Assert
        self::assertCount(2, $dtos);

        self::assertSame('aa0e8400-e29b-41d4-a716-446655440005', $dtos[0]->id);
        self::assertSame('summary1@example.com', $dtos[0]->email);
        self::assertSame('Summary One', $dtos[0]->name);

        self::assertSame('bb0e8400-e29b-41d4-a716-446655440006', $dtos[1]->id);
        self::assertSame('summary2@example.com', $dtos[1]->email);
        self::assertSame('Summary Two', $dtos[1]->name);
    }

    public function testToSummaryDTOListReturnsEmptyArrayForEmptyInput(): void
    {
        // Arrange
        $models = [];

        // Act
        $dtos = UserMapper::toSummaryDTOList($models);

        // Assert
        self::assertSame([], $dtos);
    }
}
