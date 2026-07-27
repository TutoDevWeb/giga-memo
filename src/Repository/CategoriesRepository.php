<?php

namespace App\Repository;

use App\Entity\Categories;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categories>
 */
class CategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categories::class);
    }

    /**
     * @return int Returns the number of Categories objects
     */
    public function findNbCategory(Users $actuelUser): int
    {
        return count($this->createQueryBuilder('c')
            ->andWhere('c.user = :oneUser')
            ->setParameter('oneUser', $actuelUser)
            ->getQuery()
            ->getResult());
    }
}
