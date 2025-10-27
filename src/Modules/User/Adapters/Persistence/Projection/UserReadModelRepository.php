<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection;

use App\Modules\User\Application\Query\UserReadModelRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserReadModel>
 */
final class UserReadModelRepository extends ServiceEntityRepository implements UserReadModelRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserReadModel::class);
    }

    public function save(UserReadModel $readModel): void
    {
        $this->getEntityManager()->persist($readModel);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?UserReadModel
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?UserReadModel
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function delete(UserReadModel $readModel): void
    {
        $this->getEntityManager()->remove($readModel);
        $this->getEntityManager()->flush();
    }

    /**
     * @return list<UserReadModel>
     */
    public function findAll(): array
    {
        return parent::findAll();
    }
}
