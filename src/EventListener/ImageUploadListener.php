<?php

namespace App\EventListener;

use App\Entity\Images;
use App\Service\PictureService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Images::class)]
class ImageUploadListener
{
    private PictureService $pictureService;

    public function __construct(PictureService $pictureService)
    {
        $this->pictureService = $pictureService;
    }

    public function preRemove(Images $image, PreRemoveEventArgs $event): void
    {

        $this->pictureService->delete($image);
    }
}
