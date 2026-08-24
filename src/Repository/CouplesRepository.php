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

    public function findNextSelectRun(Faqs $faq): ?Couples
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

    public function findNextSelectReview(Faqs $faq): ?Couples
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

    public function restartTodoRun(Faqs $faq): void
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
    public function restartTodoReview(Faqs $faq): void
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
    public function resetSelectReview(Faqs $faq): void
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

    /**
     * Calcule en une seule requête les 4 compteurs affichés sur les écrans
     * run/review d'une FAQ (todoRun, todoReview, selectRun, selectReview),
     * via des agrégats conditionnels (SUM(CASE WHEN ...)), au lieu des 4
     * requêtes séparées countTodoRun/countTodoReview/countSelectRun/
     * countSelectReview (cf. AUDIT.md, section performance).
     *
     * Note : selectRun ne filtre sur aucun flag "selectRun" (qui n'existe pas
     * sur l'entité Couples), il compte tous les couples de la FAQ. Ce nom
     * hérité est conservé tel quel pour l'instant (cf. AUDIT.md, section
     * "Qualité du code").
     */
    public function countAll(Faqs $faq): CouplesCounters
    {
        $result = $this->createQueryBuilder('c')
            ->select(
                'COALESCE(SUM(CASE WHEN c.pendingForRun = true THEN 1 ELSE 0 END), 0) AS todoRun',
                'COALESCE(SUM(CASE WHEN c.pendingForReview = true AND c.flaggedForReview = true THEN 1 ELSE 0 END), 0) AS todoReview',
                'COUNT(c.id) AS selectRun',
                'COALESCE(SUM(CASE WHEN c.flaggedForReview = true THEN 1 ELSE 0 END), 0) AS selectReview',
            )
            ->andWhere('c.faq = :faq')
            ->setParameter('faq', $faq)
            ->getQuery()
            ->getSingleResult();

        return new CouplesCounters(
            todoRun: (int) $result['todoRun'],
            todoReview: (int) $result['todoReview'],
            selectRun: (int) $result['selectRun'],
            selectReview: (int) $result['selectReview'],
        );
    }
}
