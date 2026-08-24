<?php

namespace App\Tests\Repository;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Users;
use App\Repository\ImagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ImagesRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ImagesRepository $repository;
    private Users $user;
    private Users $otherUser;
    private Categories $category;
    private Faqs $faq;
    private Couples $couple;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(ImagesRepository::class);

        $this->user = new Users();
        $this->user->setEmail('images-repository-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->otherUser = new Users();
        $this->otherUser->setEmail('images-repository-other-'.uniqid().'@example.com');
        $this->otherUser->setPassword('not-used');
        $this->em->persist($this->otherUser);

        $this->category = new Categories();
        $this->category->setName('Images Repository Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Images Repository Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->couple = new Couples();
        $this->couple->setNum(1);
        $this->couple->setFaq($this->faq);
        $this->couple->setUser($this->user);
        $this->couple->setFlaggedForReview(false);
        $this->em->persist($this->couple);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->category->getId();
        $userId = $this->user->getId();
        $otherUserId = $this->otherUser->getId();
        $this->em->clear();

        // orphanRemoval en cascade : catégorie -> faq -> couples -> images.
        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        foreach ([$userId, $otherUserId] as $id) {
            $user = $this->em->find(Users::class, $id);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createImage(Users $user, string $name): Images
    {
        $image = new Images();
        $image->setName($name);
        $image->setUser($user);
        $image->setCouple($this->couple);
        $this->em->persist($image);

        return $image;
    }

    public function testCountByUserReturnsZeroWhenUserHasNoImage(): void
    {
        $this->assertSame(0, $this->repository->countByUser($this->user));
    }

    public function testCountByUserCountsOnlyOwnImages(): void
    {
        $this->createImage($this->user, 'photo1.png');
        $this->createImage($this->user, 'photo2.png');
        $this->createImage($this->otherUser, 'photo-autre-user.png');
        $this->em->flush();

        $this->assertSame(2, $this->repository->countByUser($this->user));
    }
}
