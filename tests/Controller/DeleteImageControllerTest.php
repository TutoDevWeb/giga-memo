<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent DeleteImageController, appelé depuis le formulaire
 * d'édition d'un couple pour supprimer une image déjà uploadée.
 */
class DeleteImageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $owner;
    private Users $stranger;
    private Categories $category;
    private Faqs $faq;
    private Couples $couple;
    private Images $image;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->owner = new Users();
        $this->owner->setEmail('delete-image-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('delete-image-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Delete Image Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Delete Image Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->couple = new Couples();
        $this->couple->setNum(1);
        $this->couple->setFaq($this->faq);
        $this->couple->setUser($this->owner);
        $this->couple->setQuestion('Question');
        $this->couple->setPendingForRun(true);
        $this->couple->setPendingForReview(true);
        $this->couple->setFlaggedForReview(false);
        $this->em->persist($this->couple);

        $this->image = new Images();
        $this->image->setName('image_de_test.png');
        $this->image->setUser($this->owner);
        $this->image->setCouple($this->couple);
        $this->em->persist($this->image);

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

    /**
     * Récupère un jeton CSRF réellement valide en le lisant sur l'attribut
     * data-token du lien "Supprimer l'image", tel que rendu par le template
     * d'édition d'un couple (couples/_form.html.twig, uniquement en formType 'update').
     */
    private function fetchImageDeleteToken(int $imageId): string
    {
        $crawler = $this->client->request('GET', '/couples/update/list/'.$this->faq->getId().'/'.$this->couple->getId());
        $link = $crawler->filter('a[href*="/couple/suppression-image/'.$imageId.'"]');

        $this->assertGreaterThan(0, $link->count(), 'Lien de suppression d\'image introuvable pour l\'image '.$imageId);

        return $link->attr('data-token');
    }

    public function testDeleteImageWithValidTokenRemovesImage(): void
    {
        $this->client->loginUser($this->owner);

        $imageId = $this->image->getId();
        $token = $this->fetchImageDeleteToken($imageId);

        $this->client->request(
            'DELETE',
            '/couple/suppression-image/'.$imageId,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => $token])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringStartsWith('OK', $data['message']);

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($this->em->find(Images::class, $imageId));
    }

    public function testDeleteImageWithInvalidTokenDoesNothing(): void
    {
        $this->client->loginUser($this->owner);

        $imageId = $this->image->getId();

        $this->client->request(
            'DELETE',
            '/couple/suppression-image/'.$imageId,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => 'jeton-invalide'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'KO'], $data);

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Images::class, $imageId));
    }

    public function testDeleteImageIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request(
            'DELETE',
            '/couple/suppression-image/'.$this->image->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => 'peu-importe'])
        );

        $this->assertResponseStatusCodeSame(403);

        $this->em->clear();
        $this->assertNotNull($this->em->find(Images::class, $this->image->getId()));
    }
}
