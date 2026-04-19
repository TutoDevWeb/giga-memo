<?php

namespace App\Controller\Main;

use App\Entity\Faqs;
use App\Repository\CouplesRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\SubmitButton;

class MainController extends AbstractController
{
    #[Route('/{id_faq<\d+>?}', name: 'app_main_index')]
    public function index(
        Request $request,
        #[MapEntity(id: 'id_faq')] ?Faqs $faq
    ): Response {

        $form = $this->createFormBuilder()
            ->add('faq', EntityType::class, [
                'class' => Faqs::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir ... ',
                'data' => $faq,
            ])
            ->add('run', SubmitType::class)
            ->add('edit', SubmitType::class)
            ->getForm();

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
            'ariane' => ['index' => true],
            'form' => $form
        ]);
    }

    #[Route('/mode-run/{id_faq<\d+>}', name: 'app_main_run')]
    public function run(
        CouplesRepository $repo,
        Request $request,
        #[MapEntity(id: 'id_faq')] Faqs $faq
    ): Response {


        $nbTodoRun = $repo->countTodoRun($faq);
        $nbTodoReview = $repo->countTodoReview($faq);
        $nbSelectRun = $repo->countSelectRun($faq);
        $nbSelectReview = $repo->countSelectReview($faq);

        return $this->render('main/mode-run.html.twig', [
            'ariane' => ['index' => true, 'run' => true],
            'faq' => $faq,
            'nbTodoRun' => $nbTodoRun,
            'nbTodoReview' => $nbTodoReview,
            'nbSelectRun' => $nbSelectRun,
            'nbSelectReview' => $nbSelectReview,
        ]);
    }

    #[Route('/mode-edit/{id_faq<\d+>?}', name: 'app_main_edit')]
    public function edit(
        #[MapEntity(id: 'id_faq')] ?Faqs $faq
    ): Response {

        return $this->render('main/mode-edit.html.twig', [
            'ariane' => ['index' => true, 'edit' => true],
            'faq' => $faq ?? null
        ]);
    }
}
