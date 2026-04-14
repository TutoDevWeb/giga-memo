<?php

namespace App\Service;

use App\Entity\Couples;
use App\Entity\Images;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PictureService
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function upload(
        EntityManagerInterface $entityManager,
        Couples $couple,
        array $uploadedFiles,
    ): void {
        $idc = $couple->getId();

        // Récupérer le nombre actuel de photos en database pour cette annonce

        foreach ($uploadedFiles as $uploadedFile) {
            // On récupère le mime de l'image
            $mime = getimagesize($uploadedFile);

            if (false !== $mime && 'image/png' === $mime['mime']) {
                // On donne un nouveau nom au fichier avant de le tranférer
                $relFilename = $idc.'-'.md5(uniqid(rand(), true)).'.png';

                $absImagesDir = $this->params->get('images_directory');

                $uploadedFile->move($absImagesDir, $relFilename);

                // On teste que le fichier physique existe bien avant de mettre son nom en database
                if (\file_exists($absImagesDir.$relFilename)) {
                    $image = new Images();
                    $image->setName($relFilename);
                    $couple->addImage($image);
                    // On ne persiste que couple car il y a un cascade: ['persist']) dans l'entité Couples
                    $entityManager->persist($couple);
                    $entityManager->flush();
                }
            }
        }
    }

    public function delete(
        Images $image,
    ): bool {
        $success = true;

        // répertoire des Images
        $absImagesDir = $this->params->get('images_directory');

        // Noms des fichiers en absolu pour les image
        $absImageFilename = $absImagesDir.$image->getName();

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
