<?php

namespace App\EventListener;

use App\Entity\Images;
use App\Service\PictureService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Images::class)]
class ImageDeleteListener
{
    public function __construct(private readonly PictureService $pictureService)
    {
    }

    public function preRemove(Images $image, PreRemoveEventArgs $event): void
    {

        $this->pictureService->delete($image);
    }
}
