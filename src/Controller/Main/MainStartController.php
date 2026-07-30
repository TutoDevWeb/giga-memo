<?php

namespace App\Controller\Main;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Form\CategoriesType;
use App\Form\FaqFormType;
use App\Repository\CategoriesRepository;
use App\Repository\FaqsRepository;
use App\Security\Voter\ResourceOwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainStartController extends AbstractController
{
    #[Route('/start-create-category', name: 'app_main_start_create_category')]
    public function createCategory(
        Request $request,
        CategoriesRepository $categoriesRepository,
        EntityManagerInterface $entityManager
    ): Response {

        // Si on est ici, on vérifie que le user connecté n'a pas de categories
        if ($categoriesRepository->findNbCategory($this->getUser()) === 0) {

            $cat = new Categories;

            $form = $this->createForm(CategoriesType::class);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $cat = $form->getData();
                $cat->setUser($this->getUser());

                $entityManager->persist($cat);

                $entityManager->flush();

                return $this->redirectToRoute('app_main_index');
            }

            // On affiche le formulaire de création de catégorie
            return $this->render('main/index_start_create_category.html.twig', [
                'form' => $form
            ]);
        } else {
            throw $this->createAccessDeniedException('Erreur => Il existe une catégorie');
        }
    }

    #[Route('/start-create-faq', name: 'app_main_start_create_faq')]
    public function createFaq(
        Request $request,
        FaqsRepository $faqsRepository,
        EntityManagerInterface $entityManager
    ): Response {

        // Si on est ici, on vérifie que le user connecté n'a pas de faqs
        if ($faqsRepository->findNbFaq($this->getUser()) === 0) {

            $form = $this->createForm(FaqFormType::class, null, [
                'user' => $this->getUser()
            ]);

            $faq = new Faqs;

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $faq = $form->getData();
                $faq->setUser($this->getUser());

                // On vérifie que la catégorie parente appartient bien à l'utilisateur connecté.
                $this->denyAccessUnlessGranted(ResourceOwnerVoter::NEW, $faq->getCategory());

                $entityManager->persist($faq);

                $entityManager->flush();

                return $this->redirectToRoute('app_main_index');
            }

            // On affiche le formulaire de création de catégorie
            return $this->render('main/index_start_create_faq.html.twig', [
                'form' => $form
            ]);
        } else {
            throw $this->createAccessDeniedException('Erreur => Il existe une catégorie');
        }
    }
}
