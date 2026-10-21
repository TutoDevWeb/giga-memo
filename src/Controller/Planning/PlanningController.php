<?php

namespace App\Controller\Planning;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use App\Service\PlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PlanningController extends AbstractController
{

    #[Route('/planning', name: 'app_planning_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PlanningService $planningService
    ): Response {

        $planning = new Planning();
        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);


        $comment = '';
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \Symfony\Component\Form\Form $form */
            $clickedButton = $form->getClickedButton();

            if ($clickedButton && 'build' === $clickedButton->getName()) {

                $dateStart = new \DateTimeImmutable('today');

                $revisionDays = $planningService->get(
                    $dateStart,
                    $planning->getTimeFrame(),
                    $planning->getFrequency()
                );

                $comment = 'Générer et afficher le planning';

                $this->addFlash('success', $comment);
            }

            if ($clickedButton && 'save' === $clickedButton->getName()) {

                $comment = 'Enregistrer et afficher le planning';

                $entityManager->persist($planning);
                $entityManager->flush();

                $this->addFlash('success', $comment);
            }


            if ($clickedButton && 'delete' === $clickedButton->getName()) {

                $comment = 'Supprimer le planning';

                $this->addFlash('success', $comment);
            }
        } else {
            $comment = 'Générer et afficher le planning courant';

            // Lire status en database
            $status = false;

            // Si il y a déjà un planning d'enregistré
            if ($status === true) {

                // Lire dateStart en database
                $dateStart = new \DateTimeImmutable('today - 30 days');
            } else {
                $dateStart = new \DateTimeImmutable('today');
            }

            $revisionDays = $planningService->get($dateStart);
        }

        return $this->render('planning/index.html.twig', [
            'comment' => $comment,
            'planning' => $planning,
            'revisionDays' => $revisionDays ?? null,
            'form' => $form,
        ]);
    }
}
