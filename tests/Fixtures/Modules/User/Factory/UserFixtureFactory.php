<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Modules\User\Factory;

use App\Modules\User\Domain\Builder\UserBuilder;
use App\Modules\User\Domain\Entity\User;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Fixture factory service for User entities in tests.
 *
 * Combines Faker data generation with persistence capabilities.
 * Inject this service in your tests to easily create and persist users.
 *
 * Usage in tests:
 *     $this->users = self::getContainer()->get(UserFixtureFactory::class);
 *
 *     // Quick create and persist
 *     $user = $this->users->createApproved();
 *
 *     // With customization (callback)
 *     $user = $this->users->createApproved(fn($b) => $b->withEmail('test@example.com'));
 *
 *     // With builder pattern
 *     $user = $this->users->approved()->withEmail('test@example.com')->build();
 *     $this->users->persist($user);
 *
 *     // Create many
 *     $users = $this->users->createMany(10);
 */
final class UserFixtureFactory
{
    private Generator $faker;

    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * Create a UserBuilder pre-filled with fake data.
     *
     * Returns a builder that can be further customized before building.
     *
     * @example $builder = $this->users->new()->withEmail('custom@example.com');
     */
    public function new(): UserBuilder
    {
        return UserBuilder::new()
            ->withId($this->faker->uuid())
            ->withEmail($this->faker->unique()->safeEmail())
            ->withName($this->faker->name())
        ;
    }

    /**
     * Create an approved user builder with fake data.
     *
     * @example $user = $this->users->approved()->build();
     */
    public function approved(): UserBuilder
    {
        return $this->new()->approved();
    }

    /**
     * Create a pending user builder with fake data.
     *
     * @example $user = $this->users->pending()->build();
     */
    public function pending(): UserBuilder
    {
        return $this->new()->pending();
    }

    /**
     * Persist a user to the database.
     *
     * Accepts either a User or a UserBuilder (will build it automatically).
     *
     * @example $this->users->persist($user);
     * @example $this->users->persist($this->users->new()->withEmail('test@example.com'));
     */
    public function persist(User|UserBuilder $userOrBuilder): User
    {
        $user = $userOrBuilder instanceof UserBuilder
            ? $userOrBuilder->build()
            : $userOrBuilder;

        $this->repository->save($user);

        return $user;
    }

    /**
     * Create a user with fake data and persist it.
     *
     * Optionally customize the user via callback.
     *
     * @param null|callable(UserBuilder): void $callback Callback to customize the builder
     *
     * @example $user = $this->users->create();
     * @example $user = $this->users->create(fn($b) => $b->withEmail('test@example.com'));
     */
    public function create(?callable $callback = null): User
    {
        $builder = $this->new();

        if (null !== $callback) {
            $callback($builder);
        }

        return $this->persist($builder->build());
    }

    /**
     * Create an approved user with fake data and persist it.
     *
     * @param null|callable(UserBuilder): void $callback Callback to customize the builder
     *
     * @example $user = $this->users->createApproved();
     * @example $user = $this->users->createApproved(fn($b) => $b->withEmail('admin@example.com'));
     */
    public function createApproved(?callable $callback = null): User
    {
        $builder = $this->approved();

        if (null !== $callback) {
            $callback($builder);
        }

        return $this->persist($builder->build());
    }

    /**
     * Create a pending user with fake data and persist it.
     *
     * @param null|callable(UserBuilder): void $callback Callback to customize the builder
     *
     * @example $user = $this->users->createPending();
     * @example $user = $this->users->createPending(fn($b) => $b->withEmail('pending@example.com'));
     */
    public function createPending(?callable $callback = null): User
    {
        $builder = $this->pending();

        if (null !== $callback) {
            $callback($builder);
        }

        return $this->persist($builder->build());
    }

    /**
     * Create and persist multiple users with fake data.
     *
     * @param null|callable(UserBuilder): void $callback Callback to customize each builder
     *
     * @return User[]
     *
     * @example $users = $this->users->createMany(10);
     * @example $users = $this->users->createMany(5, fn($b) => $b->approved());
     */
    public function createMany(int $count, ?callable $callback = null): array
    {
        $users = [];

        for ($i = 0; $i < $count; ++$i) {
            $users[] = $this->create($callback);
        }

        return $users;
    }

    /**
     * Get a raw UserBuilder for manual construction (no fake data).
     *
     * Use this when you need full control without Faker.
     *
     * @example $user = $this->users->builder()
     *     ->withId('123')
     *     ->withEmail('real@example.com')
     *     ->withName('Real User')
     *     ->build();
     */
    public function builder(): UserBuilder
    {
        return UserBuilder::new();
    }
}
