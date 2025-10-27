<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\Builder;

use App\Modules\User\Domain\Builder\UserBuilder;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\ValueObject\UserStatus;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Modules\User\Domain\Builder\UserBuilder
 *
 * @internal
 */
final class UserBuilderUnitTest extends TestCase
{
    public function testBuildCreatesPendingUserWithAllRequiredFields(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withId('550e8400-e29b-41d4-a716-446655440000')
            ->withEmail('john.doe@example.com')
            ->withName('John Doe')
        ;

        // Act
        $user = $builder->build();

        // Assert
        self::assertInstanceOf(User::class, $user);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $user->getId());
        self::assertSame('john.doe@example.com', $user->getEmail()->toString());
        self::assertSame('John Doe', $user->getName());
        self::assertTrue($user->getStatus()->equals(UserStatus::pending()));
    }

    public function testBuildCreatesApprovedUserWhenApprovedMethodCalled(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withId('660e8400-e29b-41d4-a716-446655440001')
            ->withEmail('jane.smith@example.com')
            ->withName('Jane Smith')
            ->approved()
        ;

        // Act
        $user = $builder->build();

        // Assert
        self::assertTrue($user->getStatus()->equals(UserStatus::approved()));
    }

    public function testPendingMethodKeepsUserInPendingState(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withId('770e8400-e29b-41d4-a716-446655440002')
            ->withEmail('bob.jones@example.com')
            ->withName('Bob Jones')
            ->approved()  // First approve
            ->pending()  // Then revert to pending
        ;

        // Act
        $user = $builder->build();

        // Assert
        self::assertTrue($user->getStatus()->equals(UserStatus::pending()));
    }

    public function testBuildThrowsExceptionWhenIdIsMissing(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withEmail('test@example.com')
            ->withName('Test User')
        ;

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID is required. Use withId().');

        // Act
        $builder->build();
    }

    public function testBuildThrowsExceptionWhenEmailIsMissing(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withId('880e8400-e29b-41d4-a716-446655440003')
            ->withName('Test User')
        ;

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User email is required. Use withEmail().');

        // Act
        $builder->build();
    }

    public function testBuildThrowsExceptionWhenNameIsMissing(): void
    {
        // Arrange
        $builder = UserBuilder::new()
            ->withId('990e8400-e29b-41d4-a716-446655440004')
            ->withEmail('test@example.com')
        ;

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User name is required. Use withName().');

        // Act
        $builder->build();
    }

    public function testBuilderIsFluentInterface(): void
    {
        // Arrange & Act
        $builder = UserBuilder::new()
            ->withId('aa0e8400-e29b-41d4-a716-446655440005')
            ->withEmail('fluent@example.com')
            ->withName('Fluent User')
            ->approved()
        ;

        // Assert - all methods should return the builder instance
        self::assertInstanceOf(UserBuilder::class, $builder);

        $user = $builder->build();
        self::assertInstanceOf(User::class, $user);
    }

    public function testBuilderCanBeReusedWithDifferentValues(): void
    {
        // Arrange
        $builder = UserBuilder::new();

        // Act - Build first user
        $user1 = $builder
            ->withId('bb0e8400-e29b-41d4-a716-446655440006')
            ->withEmail('user1@example.com')
            ->withName('User One')
            ->build()
        ;

        // Act - Build second user (values should override)
        $user2 = $builder
            ->withId('cc0e8400-e29b-41d4-a716-446655440007')
            ->withEmail('user2@example.com')
            ->withName('User Two')
            ->approved()
            ->build()
        ;

        // Assert
        self::assertSame('bb0e8400-e29b-41d4-a716-446655440006', $user1->getId());
        self::assertSame('user1@example.com', $user1->getEmail()->toString());
        self::assertTrue($user1->getStatus()->equals(UserStatus::pending()));

        self::assertSame('cc0e8400-e29b-41d4-a716-446655440007', $user2->getId());
        self::assertSame('user2@example.com', $user2->getEmail()->toString());
        self::assertTrue($user2->getStatus()->equals(UserStatus::approved()));
    }

    public function testNewMethodCreatesNewBuilderInstance(): void
    {
        // Act
        $builder1 = UserBuilder::new();
        $builder2 = UserBuilder::new();

        // Assert
        self::assertInstanceOf(UserBuilder::class, $builder1);
        self::assertInstanceOf(UserBuilder::class, $builder2);
        self::assertNotSame($builder1, $builder2);
    }
}
