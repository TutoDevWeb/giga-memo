<?php

namespace App\Controller\Planning;

use App\Entity\Faqs;
use App\Entity\Planning;
use App\Form\PlanningType;
use App\Security\Voter\ResourceOwnerVoter;
use App\Service\PlanningModel\Config;
use App\Service\PlanningModel\Frequency;
use App\Service\PlanningModel\Status;
use App\Service\PlanningModel\TimeFrame;
use App\Service\PlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PlanningController extends AbstractController
{

    #[Route('/planning/list/{id_faq<\d+>}', name: 'app_planning_list', methods: ['GET'])]
    public function list(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        PlanningService $planningService,
    ): Response {

        // On cherche le planning
        $planning = $faq->getPlanning();

        // Si il existe un planning
        if ($planning) {

            // Il faut restaurer le planning
            $revisionDays = $planningService->get(
                $planning->getDateStart(),
                $planning->getTimeFrame(),
                $planning->getFrequency()
            );
        }

        return $this->render('planning/list.html.twig', [
            'planning' => $planning,
            'faq' => $faq,
            'revisionDays' => $revisionDays ?? null
        ]);
    }

    #[Route('/planning/new/{id_faq<\d+>}', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {

        // Si il n'y a pas de planning on le fait.
        if ($faq->getPlanning() === null) {

            $planning = new Planning;
            $planning->setUser($this->getUser());
            $planning->setFaq($faq);
            $planning->setDateStart(new \DateTimeImmutable('today'));
            $planning->setTimeFrame(TimeFrame::ACQUISITION);
            $planning->setFrequency(Frequency::NORMAL);
            $planning->setStatus(Status::ON);

            $form = $this->createForm(PlanningType::class, $planning, [
                'save_label' => 'Créer un planning'
            ]);
            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {

                $entityManager->persist($planning);
                $entityManager->flush();

                $this->addFlash('success', 'Le planning a été créé');

                return $this->redirectToRoute('app_planning_list', ['id_faq' => $faq->getId()]);
            }

            return $this->render('planning/new.html.twig', [
                'form' => $form,
                'faq' => $faq
            ]);
        } else return $this->redirectToRoute('app_planning_edit', ['id_faq' => $faq->getId()]);
    }

    #[Route('/planning/edit/{id_faq<\d+>}', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function edit(
        PlanningService $planningService,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $planning = $faq->getPlanning();

        $form = $this->createForm(PlanningType::class, $planning, [
            'save_label' => 'Enregistrer le planning'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateStart = new \DateTimeImmutable('today');

            $revisionDays = $planningService->get(
                $dateStart,
                $planning->getTimeFrame(),
                $planning->getFrequency()
            );

            $planning->setDateStart($dateStart);

            $entityManager->persist($planning);
            $entityManager->flush();

            $this->addFlash('success', 'Le planning a été modifié');

            return $this->redirectToRoute('app_planning_list', ['id_faq' => $faq->getId()]);
        }

        return $this->render('planning/edit.html.twig', [
            'form' => $form,
            'faq' => $faq,
            'planning' => $planning,
            'revisionDays' => $revisionDays ?? null,
            'planningConfig' => $planningService->getConfig()
        ]);
    }

    #[Route('/planning/delete/{id_faq<\d+>}', name: 'app_planning_delete', methods: ['GET', 'POST'])]
    #[IsGranted(ResourceOwnerVoter::DELETE, subject: 'faq')]
    public function delete(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        EntityManagerInterface $entityManager,
    ): Response {

        $planning = $faq->getPlanning();

        // Si il y a un planning on le fait.
        if ($planning !== null) {

            $entityManager->remove($planning);
            $entityManager->flush();

            $this->addFlash('success', 'Le planning a été supprimé');
        }
        return $this->redirectToRoute('app_planning_list', ['id_faq' => $faq->getId()]);
    }
}
