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
 * Ces tests couvrent les endpoints Ajax déclenchés par le contrôleur Stimulus
 * counter-action (voir assets/controllers/counter_action_controller.js) :
 * boutons "A Revoir" / "Ne Plus Revoir" sur les pages run/review d'une FAQ.
 */
class CouplesAjaxActionsTest extends WebTestCase
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
        $this->owner->setEmail('couples-ajax-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('couples-ajax-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Ajax Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Ajax Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->couple = new Couples();
        $this->couple->setNum(1);
        $this->couple->setFaq($this->faq);
        $this->couple->setUser($this->owner);
        $this->couple->setQuestion('Question');
        $this->couple->setTodoRun(true);
        $this->couple->setTodoReview(true);
        $this->couple->setSelectReview(false);
        $this->em->persist($this->couple);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // On vide l'identity map pour forcer une ré-hydratation complète des entités :
        // sans ça, les collections OneToMany des objets construits en mémoire dans ce
        // test restent de simples ArrayCollection vides (jamais rattachées à la BDD),
        // et la cascade orphanRemoval ne « voit » rien à supprimer.
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
     * data-token du bouton correspondant, tel que rendu par le template
     * (cf. assets/controllers/counter_action_controller.js). Contrairement à un
     * appel direct au CsrfTokenManager, ceci fonctionne sans requête HTTP active.
     */
    private function fetchCsrfTokenFromButton(string $pageUrl, string $dataUrlNeedle): string
    {
        $crawler = $this->client->request('GET', $pageUrl);
        $button = $crawler->filter('[data-url*="'.$dataUrlNeedle.'"]');

        $this->assertGreaterThan(0, $button->count(), 'Bouton avec data-url*="'.$dataUrlNeedle.'" introuvable sur '.$pageUrl);

        return $button->attr('data-token');
    }

    /**
     * Le client de test reboote le kernel entre deux requêtes HTTP : l'EntityManager
     * récupéré en setUp() peut donc se retrouver détaché de la BDD. On récupère une
     * instance à jour du conteneur avant de relire l'état réel du couple.
     */
    private function reloadCouple(): Couples
    {
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        return $this->em->find(Couples::class, $this->couple->getId());
    }

    public function testSetOneReviewMarksCoupleSelectedAndReturnsUpdatedCounters(): void
    {
        $client = $this->client;
        $client->loginUser($this->owner);

        $token = $this->fetchCsrfTokenFromButton('/faqs/run/'.$this->faq->getId(), 'set-one-review');

        $client->request(
            'POST',
            '/couples/set-one-review/'.$this->couple->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => $token])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['nbSelectReview']);
        $this->assertSame(1, $data['nbTodoReview']);

        $couple = $this->reloadCouple();
        $this->assertTrue($couple->isSelectReview());
        $this->assertTrue($couple->isTodoReview());
    }

    public function testSetOneReviewWithInvalidCsrfTokenDoesNothing(): void
    {
        $client = $this->client;
        $client->loginUser($this->owner);

        $client->request(
            'POST',
            '/couples/set-one-review/'.$this->couple->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => 'jeton-invalide'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'KO'], $data);

        $this->em->refresh($this->couple);
        $this->assertFalse($this->couple->isSelectReview());
    }

    public function testSetOneReviewWithMalformedBodyReturnsBadRequest(): void
    {
        $client = $this->client;
        $client->loginUser($this->owner);

        $client->request(
            'POST',
            '/couples/set-one-review/'.$this->couple->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: 'ceci-nest-pas-du-json'
        );

        $this->assertResponseStatusCodeSame(400);

        $this->em->refresh($this->couple);
        $this->assertFalse($this->couple->isSelectReview());
    }

    public function testSetOneReviewIsDeniedForNonOwner(): void
    {
        $client = $this->client;
        $client->loginUser($this->stranger);

        // La vérification d'ownership (voter) intervient avant la vérification du
        // jeton CSRF dans le contrôleur : un jeton fictif suffit pour ce scénario.
        $client->request(
            'POST',
            '/couples/set-one-review/'.$this->couple->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => 'peu-importe'])
        );

        $this->assertResponseStatusCodeSame(403);

        $this->em->refresh($this->couple);
        $this->assertFalse($this->couple->isSelectReview());
    }

    public function testCancelOneReviewClearsSelectionAndReturnsUpdatedCounters(): void
    {
        $this->couple->setSelectReview(true);
        $this->em->flush();

        $client = $this->client;
        $client->loginUser($this->owner);

        $token = $this->fetchCsrfTokenFromButton('/faqs/review/'.$this->faq->getId(), 'cancel-one-review');

        $client->request(
            'POST',
            '/couples/cancel-one-review/'.$this->couple->getId(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['_token' => $token])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $data['nbSelectReview']);
        $this->assertSame(0, $data['nbTodoReview']);

        $couple = $this->reloadCouple();
        $this->assertFalse($couple->isSelectReview());
        $this->assertFalse($couple->isTodoReview());
    }
}
