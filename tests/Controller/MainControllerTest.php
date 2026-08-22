<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent MainController : redirections du tableau de bord selon
 * l'état de l'utilisateur (pas de catégorie / pas de faq), sécurité d'accès,
 * et les pages mode-run / mode-edit.
 */
class MainControllerTest extends WebTestCase
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
        $this->owner->setEmail('main-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('main-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Main Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Main Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->em->flush();
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

    public function testIndexRedirectsToStartCreateCategoryWhenUserHasNone(): void
    {
        $userWithoutCategory = new Users();
        $userWithoutCategory->setEmail('main-empty-'.uniqid().'@example.com');
        $userWithoutCategory->setPassword('not-used');
        $this->em->persist($userWithoutCategory);
        $this->em->flush();

        $this->client->loginUser($userWithoutCategory);

        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/start-create-category');

        $this->em->remove($userWithoutCategory);
        $this->em->flush();
    }

    public function testIndexRedirectsToStartCreateFaqWhenUserHasNoFaq(): void
    {
        $userWithCategoryOnly = new Users();
        $userWithCategoryOnly->setEmail('main-cat-only-'.uniqid().'@example.com');
        $userWithCategoryOnly->setPassword('not-used');
        $this->em->persist($userWithCategoryOnly);

        $categoryOnly = new Categories();
        $categoryOnly->setName('Categorie Sans Faq');
        $categoryOnly->setUser($userWithCategoryOnly);
        $this->em->persist($categoryOnly);

        $this->em->flush();

        $this->client->loginUser($userWithCategoryOnly);

        $this->client->request('GET', '/');

        $this->assertResponseRedirects('/start-create-faq');

        $this->em->remove($categoryOnly);
        $this->em->remove($userWithCategoryOnly);
        $this->em->flush();
    }

    public function testIndexWithOwnedFaqIsSuccessful(): void
    {
        $this->client->loginUser($this->owner);

        $this->client->request('GET', '/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithForeignFaqIsDenied(): void
    {
        // Le stranger doit avoir sa propre catégorie/faq pour dépasser les
        // redirections de démarrage et atteindre la vérification d'ownership.
        $stranger = $this->em->find(Users::class, $this->stranger->getId());

        $strangerCategory = new Categories();
        $strangerCategory->setName('Stranger Category');
        $strangerCategory->setUser($stranger);
        $this->em->persist($strangerCategory);

        $strangerFaq = new Faqs();
        $strangerFaq->setName('Stranger Faq');
        $strangerFaq->setCategory($strangerCategory);
        $strangerFaq->setUser($stranger);
        $this->em->persist($strangerFaq);

        $this->em->flush();

        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);

        $this->em->remove($strangerFaq);
        $this->em->remove($strangerCategory);
        $this->em->flush();
    }

    public function testRunIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/mode-run/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRunDisplaysCountersForOwner(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/mode-run/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Main Test Faq', $crawler->filter('body')->text());
    }

    public function testEditIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/mode-edit/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditIsSuccessfulForOwner(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/mode-edit/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Main Test Faq', $crawler->filter('body')->text());
    }

    public function testIndexRunButtonRedirectsToModeRun(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/'.$this->faq->getId());
        $this->assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="select_faq_form[_token]"]')->attr('value');

        $this->client->request('POST', '/'.$this->faq->getId(), [
            'select_faq_form' => [
                'category' => (string) $this->category->getId(),
                'faq' => (string) $this->faq->getId(),
                'run' => '',
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects('/mode-run/'.$this->faq->getId());
    }

    public function testIndexEditButtonRedirectsToModeEdit(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/'.$this->faq->getId());
        $this->assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="select_faq_form[_token]"]')->attr('value');

        $this->client->request('POST', '/'.$this->faq->getId(), [
            'select_faq_form' => [
                'category' => (string) $this->category->getId(),
                'faq' => (string) $this->faq->getId(),
                'edit' => '',
                '_token' => $token,
            ],
        ]);

        $this->assertResponseRedirects('/mode-edit/'.$this->faq->getId());
    }
}
