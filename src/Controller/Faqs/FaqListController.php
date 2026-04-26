<?php

namespace App\Controller\Faqs;

use App\Entity\Categories;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqListController extends AbstractController
{

    #[Route('/faq/list-by-category/{id}', name: 'faq_by_category')]
    public function listByCategory(Categories $category): Response
    {
        // On peut renvoyer directement un fragment de template
        return $this->render('faqs/_options.html.twig', [
            'faqs' => $category->getFaqs(),
        ]);
    }
}
