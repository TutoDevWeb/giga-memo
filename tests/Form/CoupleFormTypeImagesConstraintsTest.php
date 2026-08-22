<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Form\CoupleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Ces tests couvrent les contraintes de validation posées sur le champ
 * "images" de CoupleFormType : format (PNG uniquement) et taille maximale
 * (2 Mio). La logique métier (déplacement du fichier, quota par utilisateur,
 * ...) est testée séparément dans PictureServiceTest.
 *
 * Le champ étant "mapped: false" et non compound, il peut être soumis
 * isolément sans passer par le reste des champs du formulaire. En revanche,
 * le champ "rules" (EntityType expanded) charge sa liste de choix dès la
 * construction du formulaire : il faut donc une Faqs réellement persistée
 * pour que la requête Doctrine sous-jacente ne plante pas.
 */
class CoupleFormTypeImagesConstraintsTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;
    private Users $user;
    private Categories $category;
    private Faqs $faq;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $this->user = new Users();
        $this->user->setEmail('couple-form-images-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->category = new Categories();
        $this->category->setName('Couple Form Images Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Couple Form Images Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // Cf. PictureServiceTest : on vide l'identity map pour que l'orphanRemoval
        // de Faqs::$couples et Categories::$faqs voie bien les collections à nettoyer.
        $categoryId = $this->category->getId();
        $userId = $this->user->getId();
        $this->em->clear();

        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        $user = $this->em->find(Users::class, $userId);
        if (null !== $user) {
            $this->em->remove($user);
            $this->em->flush();
        }

        parent::tearDown();
    }

    /**
     * Soumet le formulaire complet (racine) avec le ou les fichiers donnés
     * pour le champ "images" et retourne ce seul champ, déjà validé.
     *
     * Important : la validation Symfony (constraints, dont celles du champ
     * "images") n'est déclenchée que lorsque c'est le formulaire RACINE qui
     * est soumis (voir ValidationListener::validateForm, qui ignore les
     * sous-formulaires). Soumettre uniquement $form->get('images') ne
     * déclencherait donc aucune validation.
     *
     * @param UploadedFile[] $files
     */
    private function submitImagesField(array $files): FormInterface
    {
        $couple = new Couples();
        $couple->setFaq($this->faq);
        $couple->setUser($this->user);

        $form = $this->formFactory->create(CoupleFormType::class, $couple, ['faq' => $this->faq]);

        $form->submit([
            'num' => '1',
            'rules' => [],
            'question' => 'Question test',
            'reponse' => '',
            'faq' => (string) $this->faq->getId(),
            'images' => $files,
            'from' => 'run',
        ]);

        return $form->get('images');
    }

    private function makePngFile(string $originalName, int $paddingBytes = 0): UploadedFile
    {
        $gdImage = imagecreatetruecolor(2, 2);
        $path = tempnam(sys_get_temp_dir(), 'upload');
        imagepng($gdImage, $path);

        if ($paddingBytes > 0) {
            // On complète le fichier avec des octets neutres après les données PNG
            // pour dépasser la taille max autorisée, sans changer la signature PNG
            // utilisée par la détection du mime-type.
            file_put_contents($path, str_repeat('0', $paddingBytes), \FILE_APPEND);
        }

        // Le 5e argument "test" à true permet à UploadedFile de fonctionner
        // en dehors d'une vraie requête HTTP (pas de is_uploaded_file()).
        return new UploadedFile($path, $originalName, 'image/png', null, true);
    }

    private function makeJpegFile(string $originalName): UploadedFile
    {
        $gdImage = imagecreatetruecolor(2, 2);
        $path = tempnam(sys_get_temp_dir(), 'upload');
        imagejpeg($gdImage, $path);

        return new UploadedFile($path, $originalName, 'image/jpeg', null, true);
    }

    public function testValidPngUnderSizeLimitIsAccepted(): void
    {
        $images = $this->submitImagesField([$this->makePngFile('photo.png')]);

        $this->assertTrue($images->isValid());
    }

    public function testNonPngFileIsRejectedWithFormatMessage(): void
    {
        $images = $this->submitImagesField([$this->makeJpegFile('photo.jpg')]);

        $this->assertFalse($images->isValid());
        $this->assertStringContainsString(
            'Merci de ne déposer que des images au format PNG.',
            (string) $images->getErrors(),
        );
    }

    public function testFileExceedingMaxSizeIsRejectedWithSizeMessage(): void
    {
        // Fichier PNG valide mais dont la taille dépasse la limite de 2 Mio
        // configurée dans CoupleFormType.
        $images = $this->submitImagesField([$this->makePngFile('trop-gros.png', 2 * 1024 * 1024 + 100)]);

        $this->assertFalse($images->isValid());
        $this->assertStringContainsString(
            'trop volumineux',
            (string) $images->getErrors(),
        );
    }
}
