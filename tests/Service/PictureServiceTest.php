<?php

namespace App\Tests\Service;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Users;
use App\Repository\ImagesRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureServiceTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PictureService $pictureService;
    private Filesystem $filesystem;
    private string $imagesDir;
    private Users $user;
    private Categories $category;
    private Faqs $faq;
    private Couples $couple;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->filesystem = new Filesystem();

        // Répertoire d'images dédié à ce test, distinct de public/assets/images/.
        $this->imagesDir = sys_get_temp_dir().'/giga-memo-test-images-'.uniqid().'/';
        $this->filesystem->mkdir($this->imagesDir);

        // Quota volontairement large : les tests dédiés au quota le redéfinissent
        // eux-mêmes via createPictureServiceWithQuota().
        $this->pictureService = $this->createPictureServiceWithQuota(1000);

        $this->user = new Users();
        $this->user->setEmail('picture-service-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->category = new Categories();
        $this->category->setName('Picture Service Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Picture Service Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->couple = new Couples();
        $this->couple->setNum(1);
        $this->couple->setFaq($this->faq);
        $this->couple->setUser($this->user);
        $this->couple->setQuestion('Question');
        $this->couple->setFlaggedForReview(false);
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

        $this->filesystem->remove($this->imagesDir);

        parent::tearDown();
    }

    private function createPictureServiceWithQuota(int $maxImagesPerUser): PictureService
    {
        return new PictureService(
            new ParameterBag([
                'images_directory' => $this->imagesDir,
                'images_max_per_user' => $maxImagesPerUser,
            ]),
            self::getContainer()->get(ImagesRepository::class),
        );
    }

    private function makeUploadedImageFile(int $type, string $originalName): UploadedFile
    {
        $gdImage = imagecreatetruecolor(2, 2);
        $path = tempnam(sys_get_temp_dir(), 'upload');

        if (\IMAGETYPE_PNG === $type) {
            imagepng($gdImage, $path);
            $mimeType = 'image/png';
        } else {
            imagejpeg($gdImage, $path);
            $mimeType = 'image/jpeg';
        }

        // Le 5e argument "test" à true permet à UploadedFile::move() de fonctionner
        // en dehors d'une vraie requête HTTP (pas de is_uploaded_file()).
        return new UploadedFile($path, $originalName, $mimeType, null, true);
    }

    public function testUploadCreatesImageEntityAndPhysicalFileForValidPng(): void
    {
        $file = $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo.png');

        $this->pictureService->upload($this->em, $this->couple, [$file], $this->user);

        $this->em->refresh($this->couple);
        $images = $this->couple->getImages();

        $this->assertCount(1, $images);
        $image = $images->first();
        $this->assertSame($this->user->getId(), $image->getUser()->getId());
        $this->assertFileExists($this->imagesDir.$image->getName());
    }

    public function testUploadSilentlyIgnoresValidNonPngImage(): void
    {
        // Comportement actuel documenté dans AUDIT.md : une image valide mais
        // qui n'est pas un PNG est ignorée sans retour d'erreur à l'utilisateur.
        $file = $this->makeUploadedImageFile(\IMAGETYPE_JPEG, 'photo.jpg');

        $this->pictureService->upload($this->em, $this->couple, [$file], $this->user);

        $this->em->refresh($this->couple);
        $this->assertCount(0, $this->couple->getImages());
    }

    public function testUploadIgnoresNonImageFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, "ceci n'est pas une image");
        $file = new UploadedFile($path, 'notes.txt', 'text/plain', null, true);

        $this->pictureService->upload($this->em, $this->couple, [$file], $this->user);

        $this->em->refresh($this->couple);
        $this->assertCount(0, $this->couple->getImages());
    }

    public function testDeleteRemovesPhysicalFileAndReturnsTrue(): void
    {
        $file = $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo.png');
        $this->pictureService->upload($this->em, $this->couple, [$file], $this->user);
        $this->em->refresh($this->couple);
        $image = $this->couple->getImages()->first();

        $result = $this->pictureService->delete($image);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->imagesDir.$image->getName());
    }

    public function testDeleteReturnsFalseWhenPhysicalFileIsMissing(): void
    {
        $image = new Images();
        $image->setName('does-not-exist.png');
        $image->setUser($this->user);
        $image->setCouple($this->couple);

        $result = $this->pictureService->delete($image);

        $this->assertFalse($result);
    }

    public function testUploadStopsAtQuotaAndReturnsNumberOfSkippedFiles(): void
    {
        $pictureService = $this->createPictureServiceWithQuota(2);
        $files = [
            $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-1.png'),
            $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-2.png'),
            $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-3.png'),
        ];

        $skipped = $pictureService->upload($this->em, $this->couple, $files, $this->user);

        $this->em->refresh($this->couple);
        $this->assertCount(2, $this->couple->getImages());
        $this->assertSame(1, $skipped);
    }

    public function testUploadSkipsEverythingWhenQuotaAlreadyReached(): void
    {
        $pictureService = $this->createPictureServiceWithQuota(1);
        $firstFile = $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-1.png');
        $pictureService->upload($this->em, $this->couple, [$firstFile], $this->user);

        $secondFile = $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-2.png');
        $skipped = $pictureService->upload($this->em, $this->couple, [$secondFile], $this->user);

        $this->em->refresh($this->couple);
        $this->assertCount(1, $this->couple->getImages());
        $this->assertSame(1, $skipped);
    }

    public function testUploadQuotaIsSharedAcrossCouplesOfTheSameUser(): void
    {
        // Reproduit le cas testé manuellement : le quota s'applique à
        // l'utilisateur, pas à un couple en particulier.
        $secondCouple = new Couples();
        $secondCouple->setNum(2);
        $secondCouple->setFaq($this->faq);
        $secondCouple->setUser($this->user);
        $secondCouple->setQuestion('Autre question');
        $secondCouple->setFlaggedForReview(false);
        $this->em->persist($secondCouple);
        $this->em->flush();

        $pictureService = $this->createPictureServiceWithQuota(3);

        $skippedOnFirstCouple = $pictureService->upload(
            $this->em,
            $this->couple,
            [
                $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-1.png'),
                $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-2.png'),
            ],
            $this->user,
        );

        $skippedOnSecondCouple = $pictureService->upload(
            $this->em,
            $secondCouple,
            [
                $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-3.png'),
                $this->makeUploadedImageFile(\IMAGETYPE_PNG, 'photo-4.png'),
            ],
            $this->user,
        );

        $this->em->refresh($this->couple);
        $this->em->refresh($secondCouple);

        $this->assertSame(0, $skippedOnFirstCouple);
        $this->assertSame(1, $skippedOnSecondCouple);
        $this->assertCount(2, $this->couple->getImages());
        $this->assertCount(1, $secondCouple->getImages());
    }
}
