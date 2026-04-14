<?php

namespace App\Controller\Faqs;

use App\Entity\Faqs;
use App\Form\FaqFormType;
use App\Repository\CouplesRepository;
use App\Repository\FaqsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FaqsController extends AbstractController
{
    /**
     * Ce controleur a pour but de créer une nouvelle Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Créer une Faq du Mode-Edit.
     */
    #[Route('/faqs/new', name: 'app_faqs_new')]
    public function new(EntityManagerInterface $entityManager, Request $request): Response
    {
        $faq = new Faqs();

        $form = $this->createForm(FaqFormType::class, $faq);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq = $form->getData();

            $entityManager->persist($faq);
            $entityManager->flush();

            return $this->redirectToRoute('app_main_index');
        }

        return $this->render('faqs/new.html.twig', [
            'ariane' => ['index' => true, 'create' => true],
            'form' => $form,
        ]);
    }

    /**
     * Ce controleur a pour but de modifier le nom d'une Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Modifier du Mode-Edit.
     */
    #[Route('/faqs/update/{id<\d+>}', name: 'app_faqs_update')]
    public function update(EntityManagerInterface $entityManager, FaqsRepository $repo, Request $request, Faqs $faq): Response
    {
        $form = $this->createForm(FaqFormType::class, $faq);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq = $form->getData();

            $entityManager->persist($faq);
            $entityManager->flush();

            return $this->redirectToRoute('app_main_index', ['id' => $faq->getId()]);
        }

        return $this->render('faqs/update.html.twig', [
            'ariane' => ['index' => true, 'edit' => true],
            'form' => $form,
        ]);
    }

    /**
     * Ce contrôleur a pour but de supprimer une Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Supprimer du Mode-Edit.
     */
    #[Route('/faqs/delete/{id<\d+>}', name: 'app_faqs_delete')]
    public function delete(EntityManagerInterface $entityManager, FaqsRepository $repo, Faqs $faq): Response
    {
        $entityManager->remove($faq);
        $entityManager->flush();

        return $this->redirectToRoute('app_main_index');
    }

    /**
     * Ce contrôleur a pour but de dérouler la liste de tous les QRs qui sont dans une Faq.
     * Pour ça l'utilisateur appuie sur le bouton Run du Mode-Run
     * Ensuite il appuie sur le bouton Suivant.
     */
    #[Route('/faqs/run/{id<\d+>}', name: 'app_faqs_run')]
    public function run(CouplesRepository $repo, Faqs $faq): Response
    {
        $couple = $repo->findNextSelectRun($faq);

        $nbTodoRun = $repo->countTodoRun($faq);
        $nbTodoReview = $repo->countTodoReview($faq);
        $nbSelectRun = $repo->countSelectRun($faq);
        $nbSelectReview = $repo->countSelectReview($faq);

        return $this->render('faqs/run.html.twig', [
            'ariane' => ['index' => true, 'run' => true],
            'faq' => $faq,
            'couple' => $couple,
            'nbTodoRun' => $nbTodoRun,
            'nbTodoReview' => $nbTodoReview,
            'nbSelectRun' => $nbSelectRun,
            'nbSelectReview' => $nbSelectReview,
        ]);
    }

    /**
     * Ce contrôleur a pour but de dérouler la liste des QRs qui sont A Revoir.
     * Pour ça l'utilisateur appuie sur le bouton Run Review du Mode-Run
     * Ensuite il appuie sur le bouton Suivant.
     */
    #[Route('/faqs/review/{id<\d+>}', name: 'app_faqs_review')]
    public function review(CouplesRepository $repo, Faqs $faq): Response
    {
        $couple = $repo->findNextSelectReview($faq);

        $nbTodoRun = $repo->countTodoRun($faq);
        $nbTodoReview = $repo->countTodoReview($faq);
        $nbSelectRun = $repo->countSelectRun($faq);
        $nbSelectReview = $repo->countSelectReview($faq);

        return $this->render('faqs/review.html.twig', [
            'ariane' => ['index' => true, 'run' => true],
            'faq' => $faq,
            'couple' => $couple,
            'nbTodoRun' => $nbTodoRun,
            'nbTodoReview' => $nbTodoReview,
            'nbSelectRun' => $nbSelectRun,
            'nbSelectReview' => $nbSelectReview,
        ]);
    }

    /**
     */
    #[Route('/faqs/next-run/{id<\d+>}', name: 'app_faqs_next_run')]
    public function nextRun(CouplesRepository $repo, EntityManagerInterface $em, Faqs $faq): Response
    {

        $couple = $repo->findNextSelectRun($faq);

        // Si c'est null c'est qu'il n'y a plus de couple à traiter.
        // On a fini la faq.
        if (null === $couple) {
            // On réinitialise
            $repo->restartTodoRun($faq);
        } else {
            $couple->setTodoRun(false);
        }
        $em->flush();

        // On redirige
        return $this->redirectToRoute('app_faqs_run', ['id' => $faq->getId()]);
    }

    /**
     */
    #[Route('/faqs/next-review/{id<\d+>}', name: 'app_faqs_next_review')]
    public function nextReview(CouplesRepository $repo, EntityManagerInterface $em, Faqs $faq): Response
    {

        $couple = $repo->findNextSelectReview($faq);
        if ($couple !== null) {
            $couple->setTodoReview(false);
            $em->flush();
        }

        // On a fini la faq.
        if ($repo->countTodoReview($faq) == 0) {
            $repo->restartTodoReview($faq);
        }
        $em->flush();
        // On redirige
        return $this->redirectToRoute('app_faqs_review', ['id' => $faq->getId()]);
    }


    /**
     * Ce contrôleur est appelé lorsqu'un utilisateur appuie sur le bouton Restart
     * Il met à 1 tous les booléens todoRun et todoReview de la Faqs passée en argument.
     */
    #[Route('/faqs/restart/{id<\d+>}', name: 'app_faqs_restart')]
    public function restart(CouplesRepository $repo, Faqs $faq, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('restart' . $faq->getId(), $token)) {
            // Faire le restart sur les run et les review.
            $repo->restartTodoRun($faq);
            $repo->restartTodoReview($faq);

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $nbTodoRun = $repo->countTodoRun($faq);
            $nbTodoReview = $repo->countTodoReview($faq);
            $nbSelectRun = $repo->countSelectRun($faq);
            $nbSelectReview = $repo->countSelectReview($faq);

            return new JsonResponse([
                'nbTodoRun' => $nbTodoRun,
                'nbTodoReview' => $nbTodoReview,
                'nbSelectRun' => $nbSelectRun,
                'nbSelectReview' => $nbSelectReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }

    /**
     * Ce controlleur est appelé losqu'un utilisateur appuie sur le bouton 'Reset Review'
     * Il met à 0 tous les booléens selectReview des couples qui sont dans la liste des review cad bouton 'A Revoir'.
     */
    #[Route('/faqs/reset-review/{id<\d+>}', name: 'app_faqs_reset_review')]
    public function reset_review(CouplesRepository $repo, Faqs $faq, Request $request): Response
    {
        // On récupère le jeton CSRF
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('reset-review' . $faq->getId(), $token)) {
            // Faire le reset des review
            $repo->resetSelectReview($faq);

            // Mettre les todo en concordances.
            $repo->restartTodoReview($faq);

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $nbTodoRun = $repo->countTodoRun($faq);
            $nbTodoReview = $repo->countTodoReview($faq);
            $nbSelectRun = $repo->countSelectRun($faq);
            $nbSelectReview = $repo->countSelectReview($faq);

            return new JsonResponse([
                'nbTodoRun' => $nbTodoRun,
                'nbTodoReview' => $nbTodoReview,
                'nbSelectRun' => $nbSelectRun,
                'nbSelectReview' => $nbSelectReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }
}
