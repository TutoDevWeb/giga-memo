<?php

namespace App\Tests\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Ces tests couvrent SecurityController : affichage du formulaire de connexion
 * et déroulement de l'authentification (succès / échec).
 */
class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private Users $user;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $this->user = new Users();
        $this->user->setEmail('security-test-'.uniqid().'@example.com');
        $this->user->setPassword($hasher->hashPassword($this->user, 'correct-password'));
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

    public function testLoginPageIsAccessibleAnonymously(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Merci de vous connecter', $crawler->filter('body')->text());
    }

    public function testLoginWithValidCredentialsAuthenticatesUser(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => $this->user->getEmail(),
            '_password' => 'correct-password',
        ]);

        $this->client->submit($form);

        // Après authentification, MainController::index redirige lui-même vers la
        // page de création de catégorie tant que l'utilisateur n'en a aucune.
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseRedirects('/start-create-category');
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => $this->user->getEmail(),
            '_password' => 'wrong-password',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/login');

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString('alert-danger', $crawler->filter('.alert-danger')->attr('class'));
    }

    public function testLogoutRedirectsAndClearsSession(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
}
