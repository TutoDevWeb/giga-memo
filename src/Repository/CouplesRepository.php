<?php

namespace App\Repository;

use App\Entity\Couples;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Couples>
 */
class CouplesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Couples::class);
    }

    public function findNextSelectRun($faq): ?Couples
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.todoRun = :todoRun')
            ->setParameter('todoRun', true)
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findNextSelectReview($faq): ?Couples
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.todoReview = :todoReview')
            ->setParameter('todoReview', true)
            ->andWhere('c.selectReview = :selectReview')
            ->setParameter('selectReview', true)
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function restartTodoRun($faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.todoRun', ':todoRun')
            ->setParameter('todoRun', true)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();
    }

    /**
     * On positionne le todoReview à true de tous les couples qui sont
     * dans la sélection Review (A revoir) cad qui ont le selectReview à true.
     */
    public function restartTodoReview($faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.todoReview', ':todoReview')
            ->setParameter('todoReview', true)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->andWhere('c.selectReview = :selectReview')
            ->setParameter('selectReview', true)
            ->getQuery()
            ->execute();
    }

    /**
     * On enlève tous les couples de la sélection review (a revoir)
     * Pour ça on met leur selectReview à false.
     * Du coup ils ne sont plus à faire
     * Pour ça on met le todoReview à false.
     */
    public function resetSelectReview($faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.selectReview', ':selectReview')
            ->setParameter('selectReview', false)
            ->set('c.todoReview', ':todoReview')
            ->setParameter('todoReview', false)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();
    }

    public function countTodoRun($faq): int
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.todoRun = :todoRun')
            ->setParameter('todoRun', true)
            ->andWhere('f.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();

        return count($result);
    }

    public function countTodoReview($faq): int
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.todoReview = :todoReview')
            ->setParameter('todoReview', true)
            ->andWhere('f.selectReview = :selectReview')
            ->setParameter('selectReview', true)
            ->andWhere('f.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();

        return count($result);
    }

    public function countSelectReview($faq): int
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.selectReview = :selectReview')
            ->setParameter('selectReview', true)
            ->andWhere('f.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();

        return count($result);
    }

    public function countSelectRun($faq): int
    {
        $result = $this->createQueryBuilder('f')
            ->where('f.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();

        return count($result);
    }

    //    /**
    //     * @return Couples[] Returns an array of Couples objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Couples
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
