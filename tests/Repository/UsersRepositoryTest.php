<?php

namespace App\Tests\Repository;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UsersRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UsersRepository $repository;
    private Users $user;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repository = $container->get(UsersRepository::class);

        $this->user = new Users();
        $this->user->setEmail('users-repository-test-'.uniqid().'@example.com');
        $this->user->setPassword('ancien-hash');
        $this->em->persist($this->user);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $userId = $this->user->getId();
        $this->em->clear();

        $user = $this->em->find(Users::class, $userId);
        if (null !== $user) {
            $this->em->remove($user);
            $this->em->flush();
        }

        parent::tearDown();
    }

    public function testUpgradePasswordUpdatesAndPersistsTheNewHash(): void
    {
        $this->repository->upgradePassword($this->user, 'nouveau-hash');

        $this->assertSame('nouveau-hash', $this->user->getPassword());

        $this->em->clear();
        $reloaded = $this->em->find(Users::class, $this->user->getId());
        $this->assertSame('nouveau-hash', $reloaded->getPassword());
    }

    public function testUpgradePasswordRejectsUnsupportedUser(): void
    {
        $unsupportedUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): string
            {
                return 'whatever';
            }
        };

        $this->expectException(UnsupportedUserException::class);

        $this->repository->upgradePassword($unsupportedUser, 'nouveau-hash');
    }
}
