<?php

namespace App\Controller\Faqs;

use App\Entity\Categories;
use App\Security\Voter\ResourceOwnerVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Ce controleur est utilisé par dynamic_select_controller.js
class FaqListController extends AbstractController
{
    #[Route('/faq/list-by-category/{id}', name: 'faq_by_category')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'category')]
    public function listByCategory(
        #[MapEntity(id: 'id')] Categories $category
    ): Response {
        // On peut renvoyer directement un fragment de template
        return $this->render('faqs/_options.html.twig', [
            'faqs' => $category->getFaqs(),
        ]);
    }
}
