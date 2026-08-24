<?php

namespace App\Tests\Repository;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Rules;
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

    private function createCouple(int $num, bool $pendingForRun, bool $pendingForReview, bool $flaggedForReview): Couples
    {
        $couple = new Couples();
        $couple->setNum($num);
        $couple->setFaq($this->faq);
        $couple->setUser($this->user);
        $couple->setQuestion('Question '.$num);
        $couple->setReponse('Réponse '.$num);
        $couple->setPendingForRun($pendingForRun);
        $couple->setPendingForReview($pendingForReview);
        $couple->setFlaggedForReview($flaggedForReview);

        $this->em->persist($couple);

        return $couple;
    }

    public function testFindByFaqWithImagesAndRulesLoadsCollectionsInOneQuery(): void
    {
        $rule = new Rules();
        $rule->setName('Règle de test');
        $rule->setContent('Contenu');
        $rule->setFaq($this->faq);
        $rule->setUser($this->user);
        $this->em->persist($rule);

        $withExtras = $this->createCouple(1, true, true, false);
        $withExtras->addRule($rule);

        $image = new Images();
        $image->setName('test.png');
        $image->setUser($this->user);
        $withExtras->addImage($image);

        $this->createCouple(2, true, true, false);

        $this->em->flush();
        $this->em->clear();

        $refreshedFaq = $this->em->find(Faqs::class, $this->faq->getId());
        $result = $this->repository->findByFaqWithImagesAndRules($refreshedFaq);

        $this->assertCount(2, $result);

        $this->assertSame(1, $result[0]->getNum());
        $this->assertCount(1, $result[0]->getImages());
        $this->assertSame('test.png', $result[0]->getImages()->first()->getName());
        $this->assertCount(1, $result[0]->getRules());
        $this->assertSame('Règle de test', $result[0]->getRules()->first()->getName());

        $this->assertSame(2, $result[1]->getNum());
        $this->assertCount(0, $result[1]->getImages());
        $this->assertCount(0, $result[1]->getRules());
    }

    public function testFindNextSelectRunReturnsFirstCoupleWithPendingForRunTrue(): void
    {
        $this->createCouple(1, false, true, false);
        $expected = $this->createCouple(2, true, true, false);
        $this->em->flush();

        $result = $this->repository->findNextSelectRun($this->faq);

        $this->assertNotNull($result);
        $this->assertSame($expected->getId(), $result->getId());
    }

    public function testFindNextSelectRunReturnsNullWhenNonePending(): void
    {
        $this->createCouple(1, false, true, false);
        $this->em->flush();

        $this->assertNull($this->repository->findNextSelectRun($this->faq));
    }

    public function testFindNextSelectReviewRequiresBothPendingForReviewAndFlaggedForReview(): void
    {
        // pendingForReview=true mais flaggedForReview=false : ne doit pas être renvoyé.
        $this->createCouple(1, true, true, false);
        $expected = $this->createCouple(2, true, true, true);
        $this->em->flush();

        $result = $this->repository->findNextSelectReview($this->faq);

        $this->assertNotNull($result);
        $this->assertSame($expected->getId(), $result->getId());
    }

    public function testCountAllTodoRun(): void
    {
        $this->createCouple(1, true, true, false);
        $this->createCouple(2, true, true, false);
        $this->createCouple(3, false, true, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countAll($this->faq)->todoRun);
    }

    public function testCountAllTodoReviewRequiresFlaggedForReviewToo(): void
    {
        $this->createCouple(1, true, true, true);
        $this->createCouple(2, true, true, false);
        $this->em->flush();

        $this->assertSame(1, $this->repository->countAll($this->faq)->todoReview);
    }

    public function testCountAllSelectReview(): void
    {
        $this->createCouple(1, true, true, true);
        $this->createCouple(2, true, false, true);
        $this->createCouple(3, true, true, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countAll($this->faq)->selectReview);
    }

    /**
     * Malgré son nom, selectRun ne filtre sur aucun flag "selectRun" (qui
     * n'existe pas sur l'entité Couples) : il compte tous les couples de la
     * FAQ. Ce test documente le comportement actuel (cf. AUDIT.md, section
     * "Qualité du code") plutôt que de le corriger.
     */
    public function testCountAllSelectRunActuallyCountsAllCouplesOfFaq(): void
    {
        $this->createCouple(1, true, true, false);
        $this->createCouple(2, false, false, false);
        $this->em->flush();

        $this->assertSame(2, $this->repository->countAll($this->faq)->selectRun);
    }

    public function testCountAllReturnsZeroesWhenFaqHasNoCouples(): void
    {
        $counters = $this->repository->countAll($this->faq);

        $this->assertSame(0, $counters->todoRun);
        $this->assertSame(0, $counters->todoReview);
        $this->assertSame(0, $counters->selectRun);
        $this->assertSame(0, $counters->selectReview);
    }

    public function testRestartTodoRunSetsPendingForRunTrueForAllCouplesOfFaq(): void
    {
        $this->createCouple(1, false, true, false);
        $this->createCouple(2, false, true, false);
        $this->em->flush();

        $this->repository->restartTodoRun($this->faq);
        $this->em->clear();

        $refreshedFaq = $this->em->find(Faqs::class, $this->faq->getId());
        foreach ($this->repository->findBy(['faq' => $refreshedFaq]) as $couple) {
            $this->assertTrue($couple->isPendingForRun());
        }
    }

    public function testRestartTodoReviewOnlySetsPendingForReviewTrueForSelectedCouples(): void
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

        $this->assertTrue($refreshedSelected->isPendingForReview());
        $this->assertFalse($refreshedNotSelected->isPendingForReview());
    }

    public function testResetSelectReviewClearsSelectionAndPendingForReviewForAllCouplesOfFaq(): void
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
            $this->assertFalse($couple->isFlaggedForReview());
            $this->assertFalse($couple->isPendingForReview());
        }
    }
}
