<?php

namespace App\Tests\EventListener;

use App\Entity\Images;
use App\EventListener\ImageDeleteListener;
use App\Service\PictureService;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Ce test couvre le câblage de ImageDeleteListener : il doit déléguer la
 * suppression du fichier physique à PictureService::delete(), avec l'entité
 * Images exacte transmise par l'évènement Doctrine preRemove. La logique de
 * suppression elle-même (unlink, cas fichier manquant) est testée dans
 * PictureServiceTest.
 */
class ImageDeleteListenerTest extends TestCase
{
    public function testPreRemoveDelegatesToPictureServiceDeleteWithTheSameImage(): void
    {
        $image = new Images();
        $image->setName('photo.png');

        $pictureService = $this->createMock(PictureService::class);
        $pictureService->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($image))
            ->willReturn(true);

        $listener = new ImageDeleteListener($pictureService);

        $event = new PreRemoveEventArgs($image, $this->createMock(EntityManagerInterface::class));
        $listener->preRemove($image, $event);
    }
}
