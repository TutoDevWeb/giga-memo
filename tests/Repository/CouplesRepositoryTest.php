<?php

namespace App\Tests\Repository;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Repository\CouplesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CouplesRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CouplesRepository $repository;
    private Users $user;
    private Categories $category;
    private Faqs $faq;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(CouplesRepository::class);

        $this->user = new Users();
        $this->user->setEmail('couples-repository-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->category = new Categories();
        $this->category->setName('Repository Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Repository Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // On vide l'identity map pour forcer une ré-hydratation complète des entités :
        // sans ça, les collections OneToMany des objets construits en mémoire dans ce
        // test restent de simples ArrayCollection vides (jamais rattachées à la BDD),
        // et la cascade orphanRemoval ne « voit » rien à supprimer.
        $categoryId = $this->category->getId();
        $userId = $this->user->getId();
        $this->em->clear();

        // Supprimer la catégorie déclenche, via orphanRemoval, la suppression
        // de la faq puis des couples (même mécanisme que DeleteCascadeCategoryTest).
        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        $user = $this->em->find(Users::class, $userId);
        if (null !== $user) {
            $this->em->remove($user);
            $this->em->flush();
        }

        parent::tearDown();
    }

    private function createCouple(int $num, bool $todoRun, bool $todoReview, bool $selectReview): Couples
    {
        $couple = new Couples();
        $couple->setNum($num);
        $couple->setFaq($this->faq);
        $couple->setUser($this->user);
        $couple->setQuestion('Question '.$num);
        $couple->setReponse('Réponse '.$num);
        $couple->setTodoRun($todoRun);
        $couple->setTodoReview($todoReview);
        $couple->setSelectReview($selectReview);

        $this->em->persist($couple);

        return $couple;
    }

    public function testFindNextSelectRunReturnsFirstCoupleWithTodoRunTrue(): void
    {
        $this->createCouple(1, false, true, false);
        $expected = $this->createCouple(2, true, true, false);
        $this->em->flush();

        $result = $this->repository->findNextSelectRun($this->faq);

        $this->assertNotNull($result);
        $this->assertSame($expected->getId(), $result->getId());
    }

    public function testFindNextSelectRunReturnsNullWhenNoneTodo(): void
    {
        $this->createCouple(1, false, true, false);
        $this->em->flush();

        $this->assertNull($this->repository->findNextSelectRun($this->faq));
    }

    public function testFindNextSelectReviewRequiresBothTodoReviewAndSelectReview(): void
    {
        // todoReview=true mais selectReview=false : ne doit pas être renvoyé.
        $this->createCouple(1, true, true, false);
        $expected = $this->createCouple(2, true, true, true);
        $this->em->flush();

        $result = $this->repository->findNextSelectReview($this->faq);

        $this->assertNotNull($result);
        $this->assertSame($expected->getId(), $result->getId());
    }

    public function testCountTodoRun(): void
    {
        $this->createCouple(1, true, true, false);
        $this->createCouple(2, true, true, false);
        $this->createCouple(3, false, true, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countTodoRun($this->faq));
    }

    public function testCountTodoReviewRequiresSelectReviewToo(): void
    {
        $this->createCouple(1, true, true, true);
        $this->createCouple(2, true, true, false);
        $this->em->flush();

        $this->assertSame(1, $this->repository->countTodoReview($this->faq));
    }

    public function testCountSelectReview(): void
    {
        $this->createCouple(1, true, true, true);
        $this->createCouple(2, true, false, true);
        $this->createCouple(3, true, true, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countSelectReview($this->faq));
    }

    /**
     * Malgré son nom, countSelectRun() ne filtre sur aucun flag "selectRun"
     * (qui n'existe pas sur l'entité Couples) : elle compte tous les couples
     * de la FAQ. Ce test documente le comportement actuel (cf. AUDIT.md,
     * section "Qualité du code") plutôt que de le corriger.
     */
    public function testCountSelectRunActuallyCountsAllCouplesOfFaq(): void
    {
        $this->createCouple(1, true, true, false);
        $this->createCouple(2, false, false, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countSelectRun($this->faq));
    }

    public function testRestartTodoRunSetsTodoRunTrueForAllCouplesOfFaq(): void
    {
        $this->createCouple(1, false, true, false);
        $this->createCouple(2, false, true, false);
        $this->em->flush();

        $this->repository->restartTodoRun($this->faq);
        $this->em->clear();

        $refreshedFaq = $this->em->find(Faqs::class, $this->faq->getId());
        foreach ($this->repository->findBy(['faq' => $refreshedFaq]) as $couple) {
            $this->assertTrue($couple->isTodoRun());
        }
    }

    public function testRestartTodoReviewOnlySetsTodoReviewTrueForSelectedCouples(): void
    {
        $selected = $this->createCouple(1, true, false, true);
        $notSelected = $this->createCouple(2, true, false, false);
        $this->em->flush();
        $selectedId = $selected->getId();
        $notSelectedId = $notSelected->getId();

        $this->repository->restartTodoReview($this->faq);
        $this->em->clear();

        $refreshedSelected = $this->repository->find($selectedId);
        $refreshedNotSelected = $this->repository->find($notSelectedId);

        $this->assertTrue($refreshedSelected->isTodoReview());
        $this->assertFalse($refreshedNotSelected->isTodoReview());
    }

    public function testResetSelectReviewClearsSelectionAndTodoReviewForAllCouplesOfFaq(): void
    {
        $c1 = $this->createCouple(1, true, true, true);
        $c2 = $this->createCouple(2, true, true, false);
        $this->em->flush();
        $id1 = $c1->getId();
        $id2 = $c2->getId();

        $this->repository->resetSelectReview($this->faq);
        $this->em->clear();

        foreach ([$id1, $id2] as $id) {
            $couple = $this->repository->find($id);
            $this->assertFalse($couple->isSelectReview());
            $this->assertFalse($couple->isTodoReview());
        }
    }
}
