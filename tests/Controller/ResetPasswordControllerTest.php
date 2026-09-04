<?php

namespace App\Tests\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Ces tests couvrent ResetPasswordController : demande de réinitialisation,
 * envoi de l'email, et changement de mot de passe via le lien reçu.
 */
class ResetPasswordControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    /** @var string[] emails des utilisateurs créés pendant le test, à nettoyer en tearDown */
    private array $emailsToCleanup = [];

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->emailsToCleanup as $email) {
            $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);
            if (null === $user) {
                continue;
            }

            // Les demandes de réinitialisation créées pendant le test référencent
            // l'utilisateur (FK sans cascade) : il faut les supprimer avant lui.
            foreach ($this->em->getRepository(ResetPasswordRequest::class)->findBy(['user' => $user]) as $resetRequest) {
                $this->em->remove($resetRequest);
            }

            $this->em->remove($user);
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createUser(string $password = 'ancien-mot-de-passe'): Users
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new Users();
        $email = 'reset-password-test-'.uniqid().'@example.com';
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        $this->em->persist($user);
        $this->em->flush();

        $this->emailsToCleanup[] = $email;

        return $user;
    }

    /**
     * Extrait la première URL présente dans le corps HTML du dernier email envoyé.
     */
    private function extractLinkFromLastEmail(): string
    {
        /** @var Email $message */
        $message = self::getMailerMessage(0);
        $this->assertNotNull($message);

        $matches = [];
        $found = preg_match('/href="([^"]+)"/', $message->getHtmlBody(), $matches);
        $this->assertSame(1, $found, 'Le corps de l\'email devrait contenir un lien.');

        return $matches[1];
    }

    public function testRequestPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/reinitialisation-mot-de-passe/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('réinitialisation de mon mot de passe', $crawler->filter('body')->text());
    }

    public function testRequestingResetForExistingUserSendsEmailAndRedirectsToCheckEmail(): void
    {
        $user = $this->createUser();

        $crawler = $this->client->request('GET', '/reinitialisation-mot-de-passe/');
        $form = $crawler->selectButton("J'envoie la demande")->form([
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/reinitialisation-mot-de-passe/check-email');

        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(0), 'To', $user->getEmail());
    }

    public function testRequestingResetForUnknownEmailDoesNotRevealAndRedirectsToCheckEmail(): void
    {
        $crawler = $this->client->request('GET', '/reinitialisation-mot-de-passe/');
        $form = $crawler->selectButton("J'envoie la demande")->form([
            'reset_password_request_form[email]' => 'inconnu-'.uniqid().'@example.com',
        ]);
        $this->client->submit($form);

        // Le comportement doit être identique à une adresse existante, pour ne pas
        // révéler si un compte est enregistré ou non.
        $this->assertResponseRedirects('/reinitialisation-mot-de-passe/check-email');
        self::assertEmailCount(0);
    }

    public function testFullResetPasswordFlowChangesThePassword(): void
    {
        $user = $this->createUser(password: 'ancien-mot-de-passe');

        $crawler = $this->client->request('GET', '/reinitialisation-mot-de-passe/');
        $form = $crawler->selectButton("J'envoie la demande")->form([
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);
        $this->client->submit($form);

        $resetLink = $this->extractLinkFromLastEmail();

        // Cliquer sur le lien stocke le token en session et redirige vers le formulaire.
        $this->client->request('GET', $resetLink);
        $this->assertResponseRedirects('/reinitialisation-mot-de-passe/reset');

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Je réinitialise mon mot de passe')->form([
            'change_password_form[plainPassword][first]' => 'nouveau-mot-de-passe',
            'change_password_form[plainPassword][second]' => 'nouveau-mot-de-passe',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/connexion');

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->em->clear();
        $reloaded = $this->em->find(Users::class, $user->getId());
        $this->assertTrue($hasher->isPasswordValid($reloaded, 'nouveau-mot-de-passe'));
        $this->assertFalse($hasher->isPasswordValid($reloaded, 'ancien-mot-de-passe'));
    }

    public function testResetPasswordWithMismatchedPasswordsShowsFrenchError(): void
    {
        $user = $this->createUser();

        $crawler = $this->client->request('GET', '/reinitialisation-mot-de-passe/');
        $form = $crawler->selectButton("J'envoie la demande")->form([
            'reset_password_request_form[email]' => $user->getEmail(),
        ]);
        $this->client->submit($form);

        $resetLink = $this->extractLinkFromLastEmail();

        $this->client->request('GET', $resetLink);
        $crawler = $this->client->followRedirect();

        $form = $crawler->selectButton('Je réinitialise mon mot de passe')->form([
            'change_password_form[plainPassword][first]' => 'un-mot-de-passe',
            'change_password_form[plainPassword][second]' => 'un-mot-de-passe-different',
        ]);
        $crawler = $this->client->submit($form);

        $this->assertResponseIsUnprocessable();
        $this->assertStringContainsString(
            'Les deux mots de passe doivent être identiques.',
            $crawler->filter('body')->text()
        );

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->em->clear();
        $reloaded = $this->em->find(Users::class, $user->getId());
        $this->assertTrue($hasher->isPasswordValid($reloaded, 'ancien-mot-de-passe'));
    }
}
