<?php

namespace App\Tests\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Ces tests couvrent RegistrationController : inscription, vérification de
 * l'adresse email via le lien signé reçu par mail, et renvoi de cet email.
 */
class RegistrationControllerTest extends WebTestCase
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
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createUser(bool $verified): Users
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new Users();
        $email = 'registration-test-'.uniqid().'@example.com';
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, 'correct-password'));
        $user->setIsVerified($verified);

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

    /**
     * Récupère le token CSRF du bouton "Renvoyer l'email de vérification", affiché dans
     * l'en-tête pour tout utilisateur connecté non vérifié (voir _partials/_ariane.html.twig).
     * Une requête HTTP réelle est nécessaire : passé la réponse, le conteneur n'a plus de
     * requête/session active pour générer un token via le CsrfTokenManager directement.
     */
    private function resendVerificationCsrfToken(): string
    {
        $crawler = $this->client->request('GET', '/inscription');

        return $crawler->filter('input[name="_token"]')->attr('value');
    }

    public function testRegisterPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/inscription');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString("Formulaire d'inscription", $crawler->filter('body')->text());
    }

    public function testRegisterWithValidDataCreatesUserAndSendsConfirmationEmail(): void
    {
        $email = 'registration-test-'.uniqid().'@example.com';
        $this->emailsToCleanup[] = $email;

        $crawler = $this->client->request('GET', '/inscription');

        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'un-mot-de-passe-valide',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/connexion');

        $user = $this->em->getRepository(Users::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());

        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(0), 'To', $email);

        $crawler = $this->client->followRedirect();
        $this->assertStringContainsString(
            'Un email de confirmation a été envoyé à '.$email,
            $crawler->filter('.alert-success')->text()
        );
    }

    public function testRegisterWithAlreadyUsedEmailShowsValidationError(): void
    {
        $existingUser = $this->createUser(verified: true);

        $crawler = $this->client->request('GET', '/inscription');

        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_form[email]' => $existingUser->getEmail(),
            'registration_form[plainPassword]' => 'un-mot-de-passe-valide',
        ]);

        $crawler = $this->client->submit($form);

        $this->assertResponseIsUnprocessable();
        $this->assertStringContainsString('Cette adresse email est déjà utilisée.', $crawler->filter('body')->text());
        self::assertEmailCount(0);
    }

    public function testVerifyEmailWithValidLinkMarksUserAsVerified(): void
    {
        $user = $this->createUser(verified: false);
        $this->client->loginUser($user);

        $emailVerifier = self::getContainer()->get(\App\Security\EmailVerifier::class);
        $emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('contact@super-memo.fr')
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        $signedUrl = $this->extractLinkFromLastEmail();

        $this->client->request('GET', $signedUrl);

        $this->assertResponseRedirects('/connexion');

        $this->em->clear();
        $reloaded = $this->em->find(Users::class, $user->getId());
        $this->assertTrue($reloaded->isVerified());

        // L'utilisateur étant toujours connecté, /connexion redirige lui-même
        // ailleurs (page d'accueil, éventuellement re-redirigée) : on suit toutes les redirections.
        $crawler = $this->client->followRedirect();
        while ($this->client->getResponse()->isRedirection()) {
            $crawler = $this->client->followRedirect();
        }
        $this->assertStringContainsString(
            'Votre adresse mail a été correctement vérifiée',
            $crawler->filter('.alert-success')->text()
        );
    }

    public function testVerifyEmailWithoutIdRedirectsToRegister(): void
    {
        $user = $this->createUser(verified: false);
        $this->client->loginUser($user);

        $this->client->request('GET', '/verification/email');

        $this->assertResponseRedirects('/inscription');
    }

    public function testVerifyEmailWithUnknownUserRedirectsToRegister(): void
    {
        $user = $this->createUser(verified: false);
        $this->client->loginUser($user);

        $this->client->request('GET', '/verification/email?id=999999999');

        $this->assertResponseRedirects('/inscription');
    }

    public function testResendVerificationEmailSendsNewEmailForUnverifiedUser(): void
    {
        $user = $this->createUser(verified: false);
        $this->client->loginUser($user);
        $csrfToken = $this->resendVerificationCsrfToken();

        $this->client->request('POST', '/verification/renvoi', ['_token' => $csrfToken]);

        $this->assertResponseRedirects();
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(0), 'To', $user->getEmail());
    }

    public function testResendVerificationEmailDoesNothingForAlreadyVerifiedUser(): void
    {
        // On crée l'utilisateur non vérifié pour que le formulaire (et son token CSRF)
        // soit affiché, puis on le marque vérifié juste avant l'appel.
        $user = $this->createUser(verified: false);
        $this->client->loginUser($user);
        $csrfToken = $this->resendVerificationCsrfToken();

        $user->setIsVerified(true);
        $this->em->flush();

        $this->client->request('POST', '/verification/renvoi', ['_token' => $csrfToken]);

        $this->assertResponseRedirects();
        self::assertEmailCount(0);
    }

    public function testResendVerificationEmailRequiresAuthentication(): void
    {
        // La route exige ROLE_USER : le pare-feu redirige vers /connexion avant même
        // que le token CSRF ne soit vérifié, sa valeur importe donc peu ici.
        $this->client->request('POST', '/verification/renvoi', ['_token' => 'peu-importe']);

        $this->assertResponseRedirects('/connexion');
        self::assertEmailCount(0);
    }
}
