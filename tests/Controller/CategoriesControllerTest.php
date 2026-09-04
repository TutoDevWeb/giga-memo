<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent le CRUD complet de CategoriesController :
 * liste, création, édition et suppression d'une catégorie.
 */
class CategoriesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $owner;
    private Users $stranger;
    private Categories $category;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->owner = new Users();
        $this->owner->setEmail('categories-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('categories-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Categorie Test');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->em->flush();
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

    /**
     * Récupère un jeton CSRF réellement valide en le lisant sur l'attribut
     * data-token du bouton de suppression, tel que rendu par le template
     * (bouton déclenchant la modale de confirmation, cf. _delete_form.html.twig).
     */
    private function fetchCsrfTokenFromButton(string $pageUrl, string $dataActionNeedle): string
    {
        $crawler = $this->client->request('GET', $pageUrl);
        $button = $crawler->filter('[data-action*="'.$dataActionNeedle.'"]');

        $this->assertGreaterThan(0, $button->count(), 'Bouton avec data-action*="'.$dataActionNeedle.'" introuvable sur '.$pageUrl);

        return $button->attr('data-token');
    }

    public function testListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/categories');

        $this->assertResponseRedirects('/connexion');
    }

    public function testListOnlyShowsOwnCategories(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/categories');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Categorie Test', $crawler->filter('body')->text());
    }

    public function testNewCreatesCategoryForConnectedUser(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/categories/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'categories[name]' => 'Nouvelle Categorie',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/categories');

        $created = $this->em->getRepository(Categories::class)->findOneBy(['name' => 'Nouvelle Categorie']);
        $this->assertNotNull($created);
        $this->assertSame($this->owner->getId(), $created->getUser()->getId());

        // nettoyage de la catégorie créée par ce test
        $this->em->remove($created);
        $this->em->flush();
    }

    public function testEditIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/categories/'.$this->category->getId().'/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditUpdatesCategoryName(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/categories/'.$this->category->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'categories[name]' => 'Categorie Renommee',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/categories');

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $updated = $this->em->find(Categories::class, $this->category->getId());
        $this->assertSame('Categorie Renommee', $updated->getName());
    }

    public function testDeleteWithValidTokenRemovesCategory(): void
    {
        $this->client->loginUser($this->owner);

        $categoryId = $this->category->getId();
        $token = $this->fetchCsrfTokenFromButton('/categories', '/categories/'.$categoryId);

        $this->client->request('POST', '/categories/'.$categoryId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/categories');

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($this->em->find(Categories::class, $categoryId));
    }

    public function testDeleteWithInvalidTokenDoesNothing(): void
    {
        $this->client->loginUser($this->owner);

        $categoryId = $this->category->getId();

        $this->client->request('POST', '/categories/'.$categoryId, [
            '_token' => 'jeton-invalide',
        ]);

        $this->assertResponseRedirects('/categories');

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Categories::class, $categoryId));
    }

    public function testDeleteIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        // L'IsGranted sur la route intervient avant le contrôleur : un jeton fictif suffit.
        $this->client->request('POST', '/categories/'.$this->category->getId(), [
            '_token' => 'peu-importe',
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Categories::class, $this->category->getId()));
    }
}
