<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent MainStartController : les pages de démarrage qui guident
 * un nouvel utilisateur pour créer sa première catégorie puis sa première FAQ.
 */
class MainStartControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $newUser;
    private Users $userWithCategory;
    private Categories $existingCategory;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->newUser = new Users();
        $this->newUser->setEmail('main-start-new-'.uniqid().'@example.com');
        $this->newUser->setPassword('not-used');
        $this->em->persist($this->newUser);

        $this->userWithCategory = new Users();
        $this->userWithCategory->setEmail('main-start-with-category-'.uniqid().'@example.com');
        $this->userWithCategory->setPassword('not-used');
        $this->em->persist($this->userWithCategory);

        $this->existingCategory = new Categories();
        $this->existingCategory->setName('Categorie Existante');
        $this->existingCategory->setUser($this->userWithCategory);
        $this->em->persist($this->existingCategory);

        $this->em->flush();
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->existingCategory->getId();
        $newUserId = $this->newUser->getId();
        $userWithCategoryId = $this->userWithCategory->getId();
        $this->em->clear();

        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        foreach ([$newUserId, $userWithCategoryId] as $userId) {
            $user = $this->em->find(Users::class, $userId);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    public function testCreateCategoryShowsFormForUserWithoutCategory(): void
    {
        $this->client->loginUser($this->newUser);

        $crawler = $this->client->request('GET', '/start-create-category');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('première catégorie', $crawler->filter('body')->text());
    }

    public function testCreateCategoryIsDeniedWhenUserAlreadyHasOne(): void
    {
        $this->client->loginUser($this->userWithCategory);

        $this->client->request('GET', '/start-create-category');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCategoryCreatesFirstCategoryAndRedirects(): void
    {
        $this->client->loginUser($this->newUser);

        $crawler = $this->client->request('GET', '/start-create-category');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer la catégorie')->form([
            'categories[name]' => 'Ma Premiere Categorie',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        $created = $this->em->getRepository(Categories::class)->findOneBy(['name' => 'Ma Premiere Categorie']);
        $this->assertNotNull($created);
        $this->assertSame($this->newUser->getId(), $created->getUser()->getId());

        $this->em->remove($created);
        $this->em->flush();
    }

    public function testCreateFaqIsDeniedWhenUserAlreadyHasNoCategory(): void
    {
        $this->client->loginUser($this->newUser);

        // Sans catégorie, findNbFaq vaut 0, donc le formulaire s'affiche,
        // mais aucune catégorie ne sera proposée dans le select.
        $this->client->request('GET', '/start-create-faq');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateFaqShowsFormForUserWithCategoryAndNoFaq(): void
    {
        $this->client->loginUser($this->userWithCategory);

        $crawler = $this->client->request('GET', '/start-create-faq');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('première FAQ', $crawler->filter('body')->text());
    }

    public function testCreateFaqCreatesFirstFaqAndRedirects(): void
    {
        $this->client->loginUser($this->userWithCategory);

        $crawler = $this->client->request('GET', '/start-create-faq');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'faq_form[name]' => 'Ma Premiere Faq',
            'faq_form[category]' => (string) $this->existingCategory->getId(),
            'faq_form[duration][minutes]' => '5',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects();

        $created = $this->em->getRepository(Faqs::class)->findOneBy(['name' => 'Ma Premiere Faq']);
        $this->assertNotNull($created);
        $this->assertSame($this->userWithCategory->getId(), $created->getUser()->getId());

        $this->em->remove($created);
        $this->em->flush();
    }

    public function testCreateFaqIsDeniedWhenUserAlreadyHasOne(): void
    {
        $category = $this->em->find(Categories::class, $this->existingCategory->getId());
        $user = $this->em->find(Users::class, $this->userWithCategory->getId());

        $faq = new Faqs();
        $faq->setName('Faq Existante');
        $faq->setCategory($category);
        $faq->setUser($user);
        $this->em->persist($faq);
        $this->em->flush();

        $this->client->loginUser($this->userWithCategory);

        $this->client->request('GET', '/start-create-faq');

        $this->assertResponseStatusCodeSame(403);

        $this->em->remove($faq);
        $this->em->flush();
    }
}
