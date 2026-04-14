<?php

namespace App\Controller;

use App\Entity\Rules;
use App\Form\RulesType;
use App\Repository\RulesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rules')]
final class RulesController extends AbstractController
{
    #[Route(name: 'app_rules_index', methods: ['GET'])]
    public function index(RulesRepository $rulesRepository): Response
    {
        return $this->render('rules/index.html.twig', [
            'rules' => $rulesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_rules_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rule = new Rules();
        $form = $this->createForm(RulesType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($rule);
            $entityManager->flush();

            return $this->redirectToRoute('app_rules_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rules/new.html.twig', [
            'rule' => $rule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rules_show', methods: ['GET'])]
    public function show(Rules $rule): Response
    {
        return $this->render('rules/show.html.twig', [
            'rule' => $rule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_rules_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rules $rule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RulesType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_rules_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rules/edit.html.twig', [
            'rule' => $rule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_rules_delete', methods: ['POST'])]
    public function delete(Request $request, Rules $rule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rules_index', [], Response::HTTP_SEE_OTHER);
    }
}
