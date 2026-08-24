<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent le CRUD "classique" de FaqsController (création,
 * modification, suppression) ainsi que les pages de navigation run/review
 * (hors endpoints Ajax restart/reset-review, déjà couverts par
 * FaqsAjaxActionsTest).
 */
class FaqsControllerCrudTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $owner;
    private Users $stranger;
    private Categories $category;
    private Faqs $faq;
    private Couples $couple;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->owner = new Users();
        $this->owner->setEmail('faqs-crud-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('faqs-crud-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Faqs Crud Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Faqs Crud Test Faq');
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
        $this->couple->setFlaggedForReview(true);
        $this->em->persist($this->couple);

        $this->em->flush();

        // On vide l'identity map : les objets construits en mémoire ci-dessus ont des
        // collections inverses (faq.couples, category.faqs, ...) vides tant qu'elles
        // ne sont pas rechargées depuis la BDD.
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

    public function testNewCreatesFaqForConnectedUser(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/faqs/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'faq_form[name]' => 'Nouvelle Faq',
            'faq_form[category]' => (string) $this->category->getId(),
            'faq_form[duration][minutes]' => '10',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/');

        $created = $this->em->getRepository(Faqs::class)->findOneBy(['name' => 'Nouvelle Faq']);
        $this->assertNotNull($created);
        $this->assertSame($this->owner->getId(), $created->getUser()->getId());

        $this->em->remove($created);
        $this->em->flush();
    }

    public function testUpdateIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/faqs/update/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateChangesName(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/faqs/update/'.$this->faq->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'faq_form[name]' => 'Faq Renommee',
            'faq_form[category]' => (string) $this->category->getId(),
            'faq_form[duration][minutes]' => '15',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $updated = $this->em->find(Faqs::class, $this->faq->getId());
        $this->assertSame('Faq Renommee', $updated->getName());
    }

    public function testDeleteWithValidTokenRemovesFaq(): void
    {
        $this->client->loginUser($this->owner);

        $faqId = $this->faq->getId();
        $token = $this->fetchCsrfTokenFromButton('/mode-edit/'.$faqId, '/faqs/delete/'.$faqId);

        $this->client->request('POST', '/faqs/delete/'.$faqId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/');

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($this->em->find(Faqs::class, $faqId));
    }

    public function testDeleteWithInvalidTokenDoesNothing(): void
    {
        $this->client->loginUser($this->owner);

        $faqId = $this->faq->getId();

        $this->client->request('POST', '/faqs/delete/'.$faqId, [
            '_token' => 'jeton-invalide',
        ]);

        $this->assertResponseRedirects('/');

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Faqs::class, $faqId));
    }

    public function testDeleteIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('POST', '/faqs/delete/'.$this->faq->getId(), [
            '_token' => 'peu-importe',
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->em->clear();
        $this->assertNotNull($this->em->find(Faqs::class, $this->faq->getId()));
    }

    public function testRunDisplaysNextCoupleToDo(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/faqs/run/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Question', $crawler->filter('body')->text());
    }

    public function testRunIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/faqs/run/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testNextRunMarksCoupleDoneAndRedirects(): void
    {
        $this->client->loginUser($this->owner);

        $this->client->request('GET', '/faqs/next-run/'.$this->faq->getId());

        $this->assertResponseRedirects('/faqs/run/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $couple = $this->em->find(Couples::class, $this->couple->getId());
        $this->assertFalse($couple->isPendingForRun());
    }

    public function testReviewDisplaysNextCoupleToReview(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/faqs/review/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Question', $crawler->filter('body')->text());
    }

    public function testNextReviewMarksCoupleDoneAndRedirects(): void
    {
        $this->client->loginUser($this->owner);

        $this->client->request('GET', '/faqs/next-review/'.$this->faq->getId());

        $this->assertResponseRedirects('/faqs/review/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $couple = $this->em->find(Couples::class, $this->couple->getId());
        $this->assertFalse($couple->isPendingForReview());
    }

    private function fetchCsrfTokenFromButton(string $pageUrl, string $dataActionNeedle): string
    {
        $crawler = $this->client->request('GET', $pageUrl);
        $button = $crawler->filter('[data-action*="'.$dataActionNeedle.'"]');

        $this->assertGreaterThan(0, $button->count(), 'Bouton avec data-action*="'.$dataActionNeedle.'" introuvable sur '.$pageUrl);

        return $button->attr('data-token');
    }
}
