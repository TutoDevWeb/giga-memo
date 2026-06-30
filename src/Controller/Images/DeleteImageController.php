<?php

namespace App\Controller\Images;

use App\Entity\Images;
use App\Security\Voter\ResourceOwnerVoter;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteImageController extends AbstractController
{
    #[Route('/couple/suppression-image/{id<\d+>}', name: 'couple_delete_image', methods: ['DELETE'])]
    public function deleteImage(
        EntityManagerInterface $entityManager,
        PictureService $pictureService,
        Images $image,
        Request $request,
    ): JsonResponse {

        // 🔒 Sécurisation : Si l'user connecté n'est pas le proprio, Symfony balance une 403 Access Denied
        $this->denyAccessUnlessGranted(ResourceOwnerVoter::DELETE, $image);

        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $token)) {
            $entityManager->remove($image);
            $entityManager->flush();

            $pictureService->delete($image, $this->getUser());
        } else {
            return new JsonResponse(['message' => 'KO']);
        }

        return new JsonResponse(['message' => 'OK => :' . $image->getName()]);
    }
}
