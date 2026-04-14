<?php

namespace App\Controller\Main;

use App\Entity\Faqs;
use App\Repository\CouplesRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/{id<\d+>?}', name: 'app_main_index')]
    public function index(Request $request,?Faqs $faq): Response
    {

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

            if ($form->get('run')->isClicked()) {
                return $this->redirectToRoute('app_main_run', ['id' => $faq_id]);
            }


            if ($form->get('edit')->isClicked()) {
                return $this->redirectToRoute('app_main_edit', ['id' => $faq_id]);
            }
        }

        return $this->render('main/index.html.twig', [
            'ariane' => ['index' => true],
            'form' => $form
        ]);
    }

    #[Route('/mode-run/{id<\d+>}', name: 'app_main_run')]
    public function run(CouplesRepository $repo, Request $request, Faqs $faq): Response
    {


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

    #[Route('/mode-edit/{id<\d+>?}', name: 'app_main_edit')]
    public function edit(Request $request, ?Faqs $faq): Response
    {

        return $this->render('main/mode-edit.html.twig', [
            'ariane' => ['index' => true, 'edit' => true],
            'faq' => $faq ?? null
        ]);
    }
}
