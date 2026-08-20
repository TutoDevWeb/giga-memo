<?php

namespace App\Controller\Main;

use App\Entity\Faqs;
use App\Form\SelectFaqFormType;
use App\Repository\CategoriesRepository;
use App\Repository\CouplesRepository;
use App\Repository\FaqsRepository;
use App\Security\Voter\ResourceOwnerVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class MainController extends AbstractController
{
    #[Route('/{id_faq<\d+>?}', name: 'app_main_index')]
    public function index(
        Request $request,
        #[MapEntity(id: 'id_faq')] ?Faqs $faq,
        CategoriesRepository $categoriesRepository,
        FaqsRepository $faqsRepository,
    ): Response {

        // Si l'utilisateur connecté n'a pas encore de categories
        if ($categoriesRepository->findNbCategory($this->getUser()) == 0) {
            return $this->redirectToRoute('app_main_start_create_category');
        }

        // Si l'utilisateur connecté n'a pas encore de faqs
        if ($faqsRepository->findNbFaq($this->getUser()) == 0) {
            return $this->redirectToRoute('app_main_start_create_faq');
        }

        // Si il y a une faq en argument il faut qu'elle appartienne à l'utilisateur connecté.
        if ($faq !== null) {
            $this->denyAccessUnlessGranted(ResourceOwnerVoter::VIEW, $faq);
        }

        $form = $this->createForm(SelectFaqFormType::class, null, [
            'user' => $this->getUser(), // Passe l'utilisateur connecté
            'faq' => $faq,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq_id = $form->getData()['faq']->getId();

            // InstanceOf car sinon j'ai une erreur intelephense au ->isCliked() et il y en a partout !!
            $runButton = $form->get('run');
            if ($runButton instanceof SubmitButton) {
                if ($runButton->isClicked()) {
                    return $this->redirectToRoute('app_main_run', ['id_faq' => $faq_id]);
                }
            }

            $editButton = $form->get('edit');
            if ($editButton instanceof SubmitButton) {
                if ($editButton->isClicked()) {
                    return $this->redirectToRoute('app_main_edit', ['id_faq' => $faq_id]);
                }
            }
        }

        return $this->render('main/index.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/mode-run/{id_faq<\d+>}', name: 'app_main_run')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function run(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $counters = $repo->countAll($faq);

        return $this->render('main/mode-run.html.twig', [
            'faq' => $faq,
            'nbTodoRun' => $counters->todoRun,
            'nbTodoReview' => $counters->todoReview,
            'nbSelectRun' => $counters->selectRun,
            'nbSelectReview' => $counters->selectReview,
        ]);
    }

    #[Route('/mode-edit/{id_faq<\d+>}', name: 'app_main_edit')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function edit(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        return $this->render('main/mode-edit.html.twig', [
            'faq' => $faq,
        ]);
    }
}
