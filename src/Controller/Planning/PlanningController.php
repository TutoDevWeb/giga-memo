<?php

namespace App\Controller\Planning;

use App\Entity\Faqs;
use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use App\Security\Voter\ResourceOwnerVoter;
use App\Service\PlanningModel\Status;
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
        } else {

            // C'est créer

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
            $planning->setStatus(Status::ON);

            $form = $this->createForm(PlanningType::class, $planning);
            $form->handleRequest($request);


            if ($form->isSubmitted() && $form->isValid()) {

                $entityManager->persist($planning);
                $entityManager->flush();

                $this->addFlash('success', 'Le planning a été créé');

                return $this->redirectToRoute('app_planning_list', ['id_faq' => $faq->getId()]);
            }

            return $this->render('planning/new.html.twig', [
                'form' => $form,
            ]);
        } else return $this->redirectToRoute('app_planning_edit', ['id_faq' => $faq->getId()]);
    }

    #[Route('/planning/edit/{id_faq<\d+>}', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(
        PlanningService $planningService,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $planning = $faq->getPlanning();

        $form = $this->createForm(PlanningType::class, $planning);
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
            'planning' => $planning,
            'revisionDays' => $revisionDays ?? null,
        ]);
    }



    #[Route('/planning/{id_faq<\d+>}', name: 'app_planning_index', methods: ['GET', 'POST'])]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PlanningService $planningService,
        PlanningRepository $planningRepository,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        $planning = new Planning();

        $planning->setFaq($faq);
        $planning->setUser($this->getUser());

        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);


        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                /** @var \Symfony\Component\Form\Form $form */
                $clickedButton = $form->getClickedButton();

                if ($clickedButton && 'build' === $clickedButton->getName()) {

                    $dateStart = new \DateTimeImmutable('today');

                    $revisionDays = $planningService->get(
                        $dateStart,
                        $planning->getTimeFrame(),
                        $planning->getFrequency()
                    );

                    $this->addFlash('success', 'Générer et afficher le planning');
                }

                if ($clickedButton && 'save' === $clickedButton->getName()) {

                    $dateStart = new \DateTimeImmutable('today');

                    $revisionDays = $planningService->get(
                        $dateStart,
                        $planning->getTimeFrame(),
                        $planning->getFrequency()
                    );

                    $planning->setDateStart($dateStart);
                    $planning->setStatus(Status::ON);

                    $entityManager->persist($planning);
                    $entityManager->flush();

                    $this->addFlash('success', 'Enregistrer et afficher le planning');
                }


                if ($clickedButton && 'delete' === $clickedButton->getName()) {

                    $this->addFlash('success', 'Supprimer le planning');
                }
            }
        } else {

            // Si cette faq a déjà un planning
            $oldPlanning = $faq->getPlanning();
            if ($oldPlanning !== null) {

                dump('On restore l\'ancien');

                // Restorer l'ancien Planning
                $revisionDays = $planningService->get(
                    $oldPlanning->getDateStart(),
                    $oldPlanning->getTimeFrame(),
                    $oldPlanning->getFrequency()
                );
            } else {
                dump('On genere un nouveau planning');

                $dateStart = new \DateTimeImmutable('today');
                $revisionDays = $planningService->get($dateStart);
            }
        }

        return $this->render('planning/index.html.twig', [
            'planning' => $planning,
            'revisionDays' => $revisionDays ?? null,
            'form' => $form,
        ]);
    }
}
