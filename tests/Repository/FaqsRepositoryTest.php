<?php

namespace App\Tests\Repository;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Repository\FaqsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FaqsRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FaqsRepository $repository;
    private Users $user;
    private Users $otherUser;
    private Categories $category;
    private Categories $otherUserCategory;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(FaqsRepository::class);

        $this->user = new Users();
        $this->user->setEmail('faqs-repository-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->otherUser = new Users();
        $this->otherUser->setEmail('faqs-repository-other-'.uniqid().'@example.com');
        $this->otherUser->setPassword('not-used');
        $this->em->persist($this->otherUser);

        $this->category = new Categories();
        $this->category->setName('Faqs Repository Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->otherUserCategory = new Categories();
        $this->otherUserCategory->setName('Faqs Repository Other Category');
        $this->otherUserCategory->setUser($this->otherUser);
        $this->em->persist($this->otherUserCategory);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->category->getId();
        $otherCategoryId = $this->otherUserCategory->getId();
        $this->em->clear();

        // orphanRemoval sur Categories::$faqs supprime les faqs en même temps que
        // la catégorie (même mécanisme que DeleteCascadeCategoryTest).
        foreach ([$categoryId, $otherCategoryId] as $id) {
            $category = $this->em->find(Categories::class, $id);
            if (null !== $category) {
                $this->em->remove($category);
            }
        }
        $this->em->flush();

        foreach ([$this->user->getId(), $this->otherUser->getId()] as $id) {
            $user = $this->em->find(Users::class, $id);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createFaq(Users $user, Categories $category, string $name): Faqs
    {
        $faq = new Faqs();
        $faq->setName($name);
        $faq->setCategory($category);
        $faq->setUser($user);
        $this->em->persist($faq);

        return $faq;
    }

    public function testFindNbFaqReturnsZeroWhenUserHasNone(): void
    {
        $this->assertSame(0, $this->repository->findNbFaq($this->user));
    }

    public function testFindNbFaqCountsOnlyOwnFaqs(): void
    {
        $this->createFaq($this->user, $this->category, 'Faq A');
        $this->createFaq($this->user, $this->category, 'Faq B');
        $this->createFaq($this->otherUser, $this->otherUserCategory, 'Faq de l\'autre user');
        $this->em->flush();

        $this->assertSame(2, $this->repository->findNbFaq($this->user));
    }
}
