<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent FaqListController, utilisé par dynamic_select_controller.js
 * pour peupler dynamiquement la liste des FAQs d'une catégorie.
 */
class FaqListControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $owner;
    private Users $stranger;
    private Categories $category;
    private Faqs $faq;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->owner = new Users();
        $this->owner->setEmail('faqlist-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('faqlist-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('FaqList Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('FaqList Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->em->flush();

        // On vide l'identity map : category.faqs (côté inverse) reste une
        // ArrayCollection vide tant qu'elle n'est pas rechargée depuis la BDD.
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->category->getId();
        $ownerId = $this->owner->getId();
        $strangerId = $this->stranger->getId();
        $this->em->clear();

        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        foreach ([$ownerId, $strangerId] as $userId) {
            $user = $this->em->find(Users::class, $userId);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    public function testListByCategoryReturnsFaqOptionsFragment(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/faq/list-by-category/'.$this->category->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('FaqList Test Faq', $crawler->filter('body')->text());
    }

    public function testListByCategoryIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/faq/list-by-category/'.$this->category->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListByCategoryRequiresAuthentication(): void
    {
        $this->client->request('GET', '/faq/list-by-category/'.$this->category->getId());

        $this->assertResponseRedirects('/login');
    }
}
