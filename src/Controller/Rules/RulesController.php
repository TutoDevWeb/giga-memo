<?php

namespace App\Controller\Rules;

use App\Entity\Faqs;
use App\Entity\Rules;
use App\Form\RulesType;
use App\Security\Voter\ResourceOwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rules')]
final class RulesController extends AbstractController
{
    #[Route('/list-by-faq/{id_faq<\d+>}', name: 'app_rules_list_by_faq', methods: ['GET'])]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function list_by_faq(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        return $this->render('rules/list-by-faq.html.twig', [
            'faq' => $faq,
        ]);
    }

    #[Route('/new/{id_faq<\d+>}', name: 'app_rules_new', methods: ['GET', 'POST'])]
    #[IsGranted(ResourceOwnerVoter::NEW, subject: 'faq')]
    public function new(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $rule = new Rules();
        $rule->setFaq($faq);
        $rule->setUser($this->getUser());

        $form = $this->createForm(RulesType::class, $rule);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $rule = $form->getData();
            $rule->setFaq($faq);

            $entityManager->persist($rule);
            $entityManager->flush();

            return $this->redirectToRoute('app_rules_list_by_faq', ['id_faq' => $faq->getId()]);
        }

        return $this->render('rules/new.html.twig', [
            'rule' => $rule,
            'form' => $form,
            'faq' => $faq,
        ]);
    }

    #[Route('/edit/{id_faq<\d+>}/{id_rule<\d+>}', name: 'app_rules_edit', methods: ['GET', 'POST'])]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'rule')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        #[MapEntity(id: 'id_rule')] Rules $rule,
        EntityManagerInterface $entityManager,
    ): Response {


        $form = $this->createForm(RulesType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rule->setFaq($faq);
            $rule = $form->getData();
            $entityManager->flush();

            return $this->redirectToRoute('app_rules_list_by_faq', ['id_faq' => $faq->getId()]);
        }

        return $this->render('rules/edit.html.twig', [
            'rule' => $rule,
            'form' => $form,
            'faq' => $faq,
        ]);
    }

    #[Route('/delete/{id_faq<\d+>}/{id_rule<\d+>}', name: 'app_rules_delete', methods: ['POST'])]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'rule')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        #[MapEntity(id: 'id_rule')] Rules $rule,
        EntityManagerInterface $entityManager,
    ): Response {

        if ($this->isCsrfTokenValid('delete' . $rule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rules_list_by_faq', ['id_faq' => $faq->getId()]);
    }
}
