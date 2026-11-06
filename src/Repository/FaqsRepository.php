<?php

namespace App\Repository;

use App\Entity\Faqs;
use App\Entity\Users;
use App\Service\PlanningModel\Status;
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
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :oneUser')
            ->setParameter('oneUser', $actuelUser)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getFaqsWithPlanning(Users $actuelUser): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.planning', 'p') // Fait une INNER JOIN, ce qui exclut automatiquement les faqs sans planning
            ->andWhere('f.user = :oneUser')
            ->andWhere('p.status = :status')
            ->setParameter('oneUser', $actuelUser)
            ->setParameter('status', Status::ON) // Adapte à Status::ON si tu utilises un Enum PHP natif
            ->getQuery()
            ->getResult();
    }
}
