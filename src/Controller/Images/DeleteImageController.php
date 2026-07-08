<?php

namespace App\Controller\Images;

use App\Entity\Images;
use App\Security\Voter\ResourceOwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteImageController extends AbstractController
{
    #[Route('/couple/suppression-image/{id<\d+>}', name: 'couple_delete_image', methods: ['DELETE'])]
    #[IsGranted(ResourceOwnerVoter::DELETE, subject: 'image')]
    public function deleteImage(
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'id')] Images $image,
        Request $request,
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('delete' . $image->getId(), $token)) {
            $entityManager->remove($image);
            $entityManager->flush();
        } else {
            return new JsonResponse(['message' => 'KO']);
        }

        return new JsonResponse(['message' => 'OK => :' . $image->getName()]);
    }
}
