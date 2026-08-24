<?php

namespace App\Repository;

use App\Dto\CouplesCounters;
use App\Entity\Couples;
use App\Entity\Faqs;
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

    /**
     * Charge tous les couples d'une FAQ avec leurs images et leurs règles en une
     * seule requête (leftJoin + addSelect), pour éviter le problème N+1 constaté
     * sur templates/couples/list-by-faq.html.twig (voir AUDIT.md, section performance).
     *
     * @return Couples[]
     */
    public function findByFaqWithImagesAndRules(Faqs $faq): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.images', 'i')->addSelect('i')
            ->leftJoin('c.rules', 'r')->addSelect('r')
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->orderBy('c.num', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findNextPendingForRun(Faqs $faq): ?Couples
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.pendingForRun = :pendingForRun')
            ->setParameter('pendingForRun', true)
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findNextPendingForReview(Faqs $faq): ?Couples
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.pendingForReview = :pendingForReview')
            ->setParameter('pendingForReview', true)
            ->andWhere('c.flaggedForReview = :flaggedForReview')
            ->setParameter('flaggedForReview', true)
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function restartPendingForRun(Faqs $faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.pendingForRun', ':pendingForRun')
            ->setParameter('pendingForRun', true)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();
    }

    /**
     * On positionne le pendingForReview à true de tous les couples qui sont
     * dans la sélection Review (A revoir) cad qui ont le flaggedForReview à true.
     */
    public function restartPendingForReview(Faqs $faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.pendingForReview', ':pendingForReview')
            ->setParameter('pendingForReview', true)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->andWhere('c.flaggedForReview = :flaggedForReview')
            ->setParameter('flaggedForReview', true)
            ->getQuery()
            ->execute();
    }

    /**
     * On enlève tous les couples de la sélection review (a revoir)
     * Pour ça on met leur flaggedForReview à false.
     * Du coup ils ne sont plus à faire
     * Pour ça on met le pendingForReview à false.
     */
    public function resetFlaggedForReview(Faqs $faq): void
    {
        $this->createQueryBuilder('c')
            ->update('App\Entity\Couples', 'c')
            ->set('c.flaggedForReview', ':flaggedForReview')
            ->setParameter('flaggedForReview', false)
            ->set('c.pendingForReview', ':pendingForReview')
            ->setParameter('pendingForReview', false)
            ->where('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->execute();
    }

    public function countAll(Faqs $faq): CouplesCounters
    {
        $result = $this->createQueryBuilder('c')
            ->select(
                'COALESCE(SUM(CASE WHEN c.pendingForRun = true THEN 1 ELSE 0 END), 0) AS remainingToRun',
                'COALESCE(SUM(CASE WHEN c.pendingForReview = true AND c.flaggedForReview = true THEN 1 ELSE 0 END), 0) AS remainingToReview',
                'COUNT(c.id) AS totalToRun',
                'COALESCE(SUM(CASE WHEN c.flaggedForReview = true THEN 1 ELSE 0 END), 0) AS totalToReview',
            )
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->getSingleResult();

        return new CouplesCounters(
            remainingToRun: (int) $result['remainingToRun'],
            remainingToReview: (int) $result['remainingToReview'],
            totalToRun: (int) $result['totalToRun'],
            totalToReview: (int) $result['totalToReview'],
        );
    }
}
