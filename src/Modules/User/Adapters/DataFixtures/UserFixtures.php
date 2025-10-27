<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\DataFixtures;

use App\Modules\User\Domain\Builder\UserBuilder;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Symfony\Component\Uid\Uuid;

/**
 * User Module Fixtures.
 *
 * Creates sample users for development environment.
 */
final class UserFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
        $this->faker = FakerFactory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers();
    }

    private function loadUsers(): void
    {
        // Create some pending users (awaiting approval)
        for ($i = 0; $i < 5; ++$i) {
            $user = UserBuilder::new()
                ->withId(Uuid::v4()->toRfc4122())
                ->withEmail($this->faker->unique()->safeEmail())
                ->withName($this->faker->name())
                ->pending()
                ->build()
            ;
            $this->userRepository->save($user);
        }

        // Create some approved users (ready to use the system)
        for ($i = 0; $i < 10; ++$i) {
            $user = UserBuilder::new()
                ->withId(Uuid::v4()->toRfc4122())
                ->withEmail($this->faker->unique()->safeEmail())
                ->withName($this->faker->name())
                ->approved()
                ->build()
            ;
            $this->userRepository->save($user);
        }

        // Create a known user for manual testing
        $testUser = UserBuilder::new()
            ->withId('00000000-0000-0000-0000-000000000001')
            ->withEmail('test@example.com')
            ->withName('Test User')
            ->approved()
            ->build()
        ;
        $this->userRepository->save($testUser);
    }
}
