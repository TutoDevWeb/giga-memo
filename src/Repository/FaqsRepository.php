<?php

namespace App\Repository;

use App\Entity\Faqs;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faqs>
 */
class FaqsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faqs::class);
    }

    /**
     * @return int Returns the number of faq objects
     */
    public function findNbFaq(Users $actuelUser): int
    {
        return count($this->createQueryBuilder('f')
            ->andWhere('f.user = :oneUser')
            ->setParameter('oneUser', $actuelUser)
            ->getQuery()
            ->getResult());
    }
}
