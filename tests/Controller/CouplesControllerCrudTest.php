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
 * Ces tests couvrent le CRUD "classique" de CouplesController (hors endpoints
 * Ajax, déjà couverts par CouplesAjaxActionsTest) : liste par faq, création,
 * modification et suppression d'un couple question/réponse.
 */
class CouplesControllerCrudTest extends WebTestCase
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
        $this->owner->setEmail('couples-crud-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('couples-crud-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Couples Crud Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Couples Crud Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->couple = new Couples();
        $this->couple->setNum(1);
        $this->couple->setFaq($this->faq);
        $this->couple->setUser($this->owner);
        $this->couple->setQuestion('Question existante');
        $this->couple->setTodoRun(true);
        $this->couple->setTodoReview(true);
        $this->couple->setSelectReview(false);
        $this->em->persist($this->couple);

        $this->em->flush();

        // On vide l'identity map : les objets construits en mémoire ci-dessus ont des
        // collections inverses (faq.couples, ...) vides tant qu'elles ne sont pas
        // rechargées depuis la BDD.
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

    private function fetchCsrfTokenFromButton(string $pageUrl, string $dataActionNeedle): string
    {
        $crawler = $this->client->request('GET', $pageUrl);
        $button = $crawler->filter('[data-action*="'.$dataActionNeedle.'"]');

        $this->assertGreaterThan(0, $button->count(), 'Bouton avec data-action*="'.$dataActionNeedle.'" introuvable sur '.$pageUrl);

        return $button->attr('data-token');
    }

    public function testListByFaqIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/couples/list-by-faq/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListByFaqShowsCouples(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/couples/list-by-faq/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Question existante', $crawler->filter('body')->text());
    }

    public function testNewIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/couples/new/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testNewCreatesCoupleForFaq(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/couples/new/'.$this->faq->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'couple_form[num]' => '2',
            'couple_form[question]' => 'Nouvelle question',
            'couple_form[reponse]' => 'Nouvelle reponse',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/couples/list-by-faq/'.$this->faq->getId());

        $created = $this->em->getRepository(Couples::class)->findOneBy(['question' => 'Nouvelle question']);
        $this->assertNotNull($created);
        $this->assertSame($this->faq->getId(), $created->getFaq()->getId());
        $this->assertSame($this->owner->getId(), $created->getUser()->getId());
        $this->assertTrue($created->isTodoRun());
        $this->assertTrue($created->isTodoReview());

        $this->em->remove($created);
        $this->em->flush();
    }

    public function testUpdateIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/couples/update/list/'.$this->faq->getId().'/'.$this->couple->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateFromListRedirectsToList(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/couples/update/list/'.$this->faq->getId().'/'.$this->couple->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'couple_form[num]' => '1',
            'couple_form[question]' => 'Question modifiee',
            'couple_form[reponse]' => 'Reponse modifiee',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/couples/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $updated = $this->em->find(Couples::class, $this->couple->getId());
        $this->assertSame('Question modifiee', $updated->getQuestion());
    }

    public function testUpdateFromRunRedirectsToRun(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/couples/update/run/'.$this->faq->getId().'/'.$this->couple->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'couple_form[num]' => '1',
            'couple_form[question]' => 'Question modifiee depuis run',
            'couple_form[reponse]' => 'Reponse modifiee',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/faqs/run/'.$this->faq->getId());
    }

    public function testUpdateFromReviewRedirectsToReview(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/couples/update/review/'.$this->faq->getId().'/'.$this->couple->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'couple_form[num]' => '1',
            'couple_form[question]' => 'Question modifiee depuis review',
            'couple_form[reponse]' => 'Reponse modifiee',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/faqs/review/'.$this->faq->getId());
    }

    public function testDeleteWithValidTokenRemovesCouple(): void
    {
        $this->client->loginUser($this->owner);

        $coupleId = $this->couple->getId();
        $token = $this->fetchCsrfTokenFromButton(
            '/couples/list-by-faq/'.$this->faq->getId(),
            '/couples/delete/'.$this->faq->getId().'/'.$coupleId
        );

        $this->client->request('POST', '/couples/delete/'.$this->faq->getId().'/'.$coupleId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/couples/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($this->em->find(Couples::class, $coupleId));
    }

    public function testDeleteWithInvalidTokenDoesNothing(): void
    {
        $this->client->loginUser($this->owner);

        $coupleId = $this->couple->getId();

        $this->client->request('POST', '/couples/delete/'.$this->faq->getId().'/'.$coupleId, [
            '_token' => 'jeton-invalide',
        ]);

        $this->assertResponseRedirects('/couples/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Couples::class, $coupleId));
    }

    public function testDeleteIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('POST', '/couples/delete/'.$this->faq->getId().'/'.$this->couple->getId(), [
            '_token' => 'peu-importe',
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->em->clear();
        $this->assertNotNull($this->em->find(Couples::class, $this->couple->getId()));
    }
}
