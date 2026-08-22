<?php

namespace App\Tests\Repository;

use App\Entity\Categories;
use App\Entity\Users;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CategoriesRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CategoriesRepository $repository;
    private Users $user;
    private Users $otherUser;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(CategoriesRepository::class);

        $this->user = new Users();
        $this->user->setEmail('categories-repository-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->otherUser = new Users();
        $this->otherUser->setEmail('categories-repository-other-'.uniqid().'@example.com');
        $this->otherUser->setPassword('not-used');
        $this->em->persist($this->otherUser);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $userId = $this->user->getId();
        $otherUserId = $this->otherUser->getId();
        $this->em->clear();

        // Categories.user est une FK NOT NULL sans cascade : il faut supprimer les
        // catégories créées par le test avant de pouvoir supprimer les utilisateurs.
        foreach ([$userId, $otherUserId] as $id) {
            foreach ($this->em->getRepository(Categories::class)->findBy(['user' => $id]) as $category) {
                $this->em->remove($category);
            }
        }
        $this->em->flush();

        foreach ([$userId, $otherUserId] as $id) {
            $user = $this->em->find(Users::class, $id);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createCategory(Users $user, string $name): Categories
    {
        $category = new Categories();
        $category->setName($name);
        $category->setUser($user);
        $this->em->persist($category);

        return $category;
    }

    public function testFindNbCategoryReturnsZeroWhenUserHasNone(): void
    {
        $this->assertSame(0, $this->repository->findNbCategory($this->user));
    }

    public function testFindNbCategoryCountsOnlyOwnCategories(): void
    {
        $this->createCategory($this->user, 'Categorie A');
        $this->createCategory($this->user, 'Categorie B');
        $this->createCategory($this->otherUser, 'Categorie de l\'autre user');
        $this->em->flush();

        $this->assertSame(2, $this->repository->findNbCategory($this->user));
    }
}
