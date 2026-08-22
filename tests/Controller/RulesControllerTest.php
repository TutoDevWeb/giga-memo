<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ces tests couvrent le CRUD complet de RulesController :
 * liste par faq, création, édition et suppression d'une règle.
 */
class RulesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $owner;
    private Users $stranger;
    private Categories $category;
    private Faqs $faq;
    private Rules $rule;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->owner = new Users();
        $this->owner->setEmail('rules-owner-'.uniqid().'@example.com');
        $this->owner->setPassword('not-used');
        $this->em->persist($this->owner);

        $this->stranger = new Users();
        $this->stranger->setEmail('rules-stranger-'.uniqid().'@example.com');
        $this->stranger->setPassword('not-used');
        $this->em->persist($this->stranger);

        $this->category = new Categories();
        $this->category->setName('Rules Test Category');
        $this->category->setUser($this->owner);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Rules Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->owner);
        $this->em->persist($this->faq);

        $this->rule = new Rules();
        $this->rule->setName('Regle Test');
        $this->rule->setContent('Contenu de la regle');
        $this->rule->setFaq($this->faq);
        $this->rule->setUser($this->owner);
        $this->em->persist($this->rule);

        $this->em->flush();

        // On vide l'identity map : $this->faq a été construit en mémoire et sa
        // collection $rules (côté inverse) reste une ArrayCollection vide tant
        // qu'on ne la recharge pas depuis la BDD (cf. addRule() jamais appelé ici).
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

        $this->client->request('GET', '/rules/list-by-faq/'.$this->faq->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListByFaqShowsRules(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/rules/list-by-faq/'.$this->faq->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Regle Test', $crawler->filter('body')->text());
    }

    public function testNewCreatesRuleForFaq(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/rules/new/'.$this->faq->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form([
            'rules[name]' => 'Nouvelle Regle',
            'rules[content]' => 'Nouveau contenu',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/rules/list-by-faq/'.$this->faq->getId());

        $created = $this->em->getRepository(Rules::class)->findOneBy(['name' => 'Nouvelle Regle']);
        $this->assertNotNull($created);
        $this->assertSame($this->faq->getId(), $created->getFaq()->getId());

        $this->em->remove($created);
        $this->em->flush();
    }

    public function testEditIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('GET', '/rules/edit/'.$this->faq->getId().'/'.$this->rule->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditUpdatesRule(): void
    {
        $this->client->loginUser($this->owner);

        $crawler = $this->client->request('GET', '/rules/edit/'.$this->faq->getId().'/'.$this->rule->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Modifier')->form([
            'rules[name]' => 'Regle Renommee',
            'rules[content]' => 'Contenu modifie',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/rules/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $updated = $this->em->find(Rules::class, $this->rule->getId());
        $this->assertSame('Regle Renommee', $updated->getName());
    }

    public function testDeleteWithValidTokenRemovesRule(): void
    {
        $this->client->loginUser($this->owner);

        $ruleId = $this->rule->getId();
        $token = $this->fetchCsrfTokenFromButton(
            '/rules/list-by-faq/'.$this->faq->getId(),
            '/rules/delete/'.$this->faq->getId().'/'.$ruleId
        );

        $this->client->request('POST', '/rules/delete/'.$this->faq->getId().'/'.$ruleId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/rules/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($this->em->find(Rules::class, $ruleId));
    }

    public function testDeleteWithInvalidTokenDoesNothing(): void
    {
        $this->client->loginUser($this->owner);

        $ruleId = $this->rule->getId();

        $this->client->request('POST', '/rules/delete/'.$this->faq->getId().'/'.$ruleId, [
            '_token' => 'jeton-invalide',
        ]);

        $this->assertResponseRedirects('/rules/list-by-faq/'.$this->faq->getId());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertNotNull($this->em->find(Rules::class, $ruleId));
    }

    public function testDeleteIsDeniedForNonOwner(): void
    {
        $this->client->loginUser($this->stranger);

        $this->client->request('POST', '/rules/delete/'.$this->faq->getId().'/'.$this->rule->getId(), [
            '_token' => 'peu-importe',
        ]);

        $this->assertResponseStatusCodeSame(403);

        $this->em->clear();
        $this->assertNotNull($this->em->find(Rules::class, $this->rule->getId()));
    }
}
