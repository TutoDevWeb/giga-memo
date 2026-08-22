<?php

namespace App\Service;

use App\Entity\Couples;
use App\Entity\Images;
use App\Entity\Users;
use App\Repository\ImagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PictureService
{

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly ImagesRepository $imagesRepository,
    ) {}

    /**
     * @return int Le nombre de fichiers ignorés car le quota de l'utilisateur était atteint
     */
    public function upload(
        EntityManagerInterface $entityManager,
        Couples $couple,
        array $uploadedFiles,
        Users $user,
    ): int {
        $idc = $couple->getId();
        $hasNewImages = false; // Petit flag pour savoir si on doit flush à la fin

        $maxImagesPerUser = (int) $this->params->get('images_max_per_user');
        $remainingSlots = max(0, $maxImagesPerUser - $this->imagesRepository->countByUser($user));
        $skippedForQuota = 0;

        foreach ($uploadedFiles as $uploadedFile) {
            if ($remainingSlots <= 0) {
                ++$skippedForQuota;
                continue;
            }

            $mime = getimagesize($uploadedFile);

            if (false !== $mime && 'image/png' === $mime['mime']) {
                $relFilename = $idc . '-' . md5(uniqid((string) rand(), true)) . '.png';
                $absImagesDir = $this->params->get('images_directory');
                $uploadedFile->move($absImagesDir, $relFilename);

                if (\file_exists($absImagesDir . $relFilename)) {
                    $image = new Images();
                    $image->setName($relFilename);
                    $image->setUser($user);
                    $couple->addImage($image);

                    $entityManager->persist($couple);
                    $hasNewImages = true; // On a au moins une image valide
                    --$remainingSlots;
                }
            }
        }

        // Une fois la boucle COMPLÈTE, on envoie tout en une seule fois en BDD 🚀
        if ($hasNewImages) {
            $entityManager->flush();
        }

        return $skippedForQuota;
    }

    public function delete(
        Images $image,
    ): bool {
        $success = true;

        // répertoire des Images
        $absImagesDir = $this->params->get('images_directory');

        // Noms des fichiers en absolu pour les image
        $absImageFilename = $absImagesDir . $image->getName();

        if (file_exists($absImageFilename)) {
            try {
                \unlink($absImageFilename);
            } catch (\Exception $e) {
                $success = false;
            }
        } else {
            $success = false;
        }

        return $success;
    }
}
