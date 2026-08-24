<?php

namespace App\Controller\Faqs;

use App\Controller\Trait\JsonCsrfTokenTrait;
use App\Entity\Faqs;
use App\Form\FaqFormType;
use App\Repository\CouplesRepository;
use App\Security\Voter\ResourceOwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FaqsController extends AbstractController
{
    use JsonCsrfTokenTrait;

    /**
     * Ce controleur a pour but de créer une nouvelle Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Créer une Faq du Mode-Edit.
     */
    #[Route('/faqs/new', name: 'app_faqs_new')]
    public function new(EntityManagerInterface $entityManager, Request $request): Response
    {
        $faq = new Faqs();

        // On récupère l'utilisateur connecté et on l'injecte dans l'entité
        $faq->setUser($this->getUser());


        $form = $this->createForm(FaqFormType::class, $faq, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // On vérifie que la catégorie parente appartient bien à l'utilisateur connecté.
            $this->denyAccessUnlessGranted(ResourceOwnerVoter::NEW, $faq->getCategory());

            $entityManager->persist($faq);
            $entityManager->flush();

            $this->addFlash('success', 'La FAQ "' . $faq->getName() . '" a été créée avec succès !');

            return $this->redirectToRoute('app_main_index');
        }

        return $this->render('faqs/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Ce controleur a pour but de modifier le nom d'une Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Modifier du Mode-Edit.
     */
    #[Route('/faqs/update/{id_faq<\d+>}', name: 'app_faqs_update')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function update(
        EntityManagerInterface $entityManager,
        Request $request,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        $form = $this->createForm(FaqFormType::class, $faq, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq = $form->getData();

            $entityManager->persist($faq);
            $entityManager->flush();

            $this->addFlash('success', 'La FAQ "' . $faq->getName() . '" a été modifiée avec succès !');

            return $this->redirectToRoute('app_main_index', ['id_faq' => $faq->getId()]);
        }

        return $this->render('faqs/edit.html.twig', [
            'form' => $form,
            'faq' => $faq,
        ]);
    }

    /**
     * Ce contrôleur a pour but de supprimer une Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Supprimer du Mode-Edit.
     */
    #[Route('/faqs/delete/{id_faq<\d+>}', name: 'app_faqs_delete')]
    #[IsGranted(ResourceOwnerVoter::DELETE, subject: 'faq')]
    public function delete(
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
    ): Response {


        if ($this->isCsrfTokenValid('delete' . $faq->getId(), $request->getPayload()->getString('_token'))) {

            // Là il me suffit de supprimer la faq et il y a cascade des suppressions.
            // => couples => (images et rules)
            $entityManager->remove($faq);
            $entityManager->flush();

            $this->addFlash('success', 'La FAQ "' . $faq->getName() . '" a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_main_index');
    }

    /**
     * Ce controlleur affiche un couple question réponse.
     * Avec les boutons Voir la réponse / Suivant / A revoir.
     */
    #[Route('/faqs/run/{id_faq<\d+>}', name: 'app_faqs_run')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function run(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        // findNextSelectRun va chercher le couple à afficher
        // findNextSelectRun renvoie le premier couple qui a le flag pendingForRun à true.
        $couple = $repo->findNextSelectRun($faq);

        $counters = $repo->countAll($faq);

        return $this->render('faqs/run.html.twig', [
            'faq' => $faq,
            'couple' => $couple,
            'counters' => $counters,
        ]);
    }

    /** 
     * Ce controlleur est appelé après appui sur le bouton suivant
     * Il marque le couple qui est en cours d'affichage comme fait.
     */
    #[Route('/faqs/next-run/{id_faq<\d+>}', name: 'app_faqs_next_run')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function nextRun(
        CouplesRepository $repo,
        EntityManagerInterface $em,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        // On récupère le couple en cours d'affichage
        $couple = $repo->findNextSelectRun($faq);


        // Si c'est null c'est qu'il n'y a plus de couple à traiter.
        // Théoriquement, ce cas ne se présente jamais car dans ce cas,
        // l'affichage dans faqs/run.html.twig n'affiche plus le bouton suivant.
        if ($couple !== null) {

            // On positionne le flag pendingForRun à false.
            // Ce qui marque le fait que le couple a été fait.
            $couple->setPendingForRun(false);
            $em->flush();
        }

        // On redirige pour afficher un nouveau couple
        return $this->redirectToRoute('app_faqs_run', ['id_faq' => $faq->getId()]);
    }


    /**
     * Pareil  que run mais sur la liste des QRs à revoir
     */
    #[Route('/faqs/review/{id_faq<\d+>}', name: 'app_faqs_review')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function review(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $couple = $repo->findNextSelectReview($faq);

        $counters = $repo->countAll($faq);

        return $this->render('faqs/review.html.twig', [
            'faq' => $faq,
            'couple' => $couple,
            'counters' => $counters,
        ]);
    }

    /**
     * Pareil que next-run mais sur la liste des QRs à revoir
     * Sauf qu'ici lorsque la faq est finie on la réinitialise.
     */
    #[Route('/faqs/next-review/{id_faq<\d+>}', name: 'app_faqs_next_review')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function nextReview(
        CouplesRepository $repo,
        EntityManagerInterface $em,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $couple = $repo->findNextSelectReview($faq);

        if (null !== $couple) {
            $couple->setPendingForReview(false);
            $em->flush();
        }

        // On a fini la faq.
        if (0 == $repo->countAll($faq)->todoReview) {
            $repo->restartTodoReview($faq);
        }
        $em->flush();

        // On redirige
        return $this->redirectToRoute('app_faqs_review', ['id_faq' => $faq->getId()]);
    }

    /**
     * Ce contrôleur est appelé lorsqu'un utilisateur appuie sur le bouton Restart
     * Il met à 1 tous les booléens pendingForRun et pendingForReview de la Faqs passée en argument.
     */
    #[Route('/faqs/restart/{id_faq<\d+>}', name: 'app_faqs_restart')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function restart(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
    ): Response {
        $token = $this->getCsrfTokenFromJson($request);

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('restart' . $faq->getId(), $token)) {
            // Faire le restart sur les run et les review.
            $repo->restartTodoRun($faq);
            $repo->restartTodoReview($faq);

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $counters = $repo->countAll($faq);

            return new JsonResponse([
                'nbTodoRun'      => $counters->todoRun,
                'nbTodoReview'   => $counters->todoReview,
                'nbSelectRun'    => $counters->selectRun,
                'nbSelectReview' => $counters->selectReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }

    /**
     * Ce controlleur est appelé losqu'un utilisateur appuie sur le bouton 'Reset Review'
     * Il met à 0 tous les booléens flaggedForReview des couples qui sont dans la liste des review cad bouton 'A Revoir'.
     */
    #[Route('/faqs/reset-review/{id_faq<\d+>}', name: 'app_faqs_reset_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function reset_review(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
    ): Response {
        // On récupère le jeton CSRF
        $token = $this->getCsrfTokenFromJson($request);

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('reset-review' . $faq->getId(), $token)) {
            // Faire le reset des review
            $repo->resetSelectReview($faq);

            // Mettre les todo en concordances.
            $repo->restartTodoReview($faq);

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $counters = $repo->countAll($faq);

            return new JsonResponse([
                'nbTodoRun' => $counters->todoRun,
                'nbTodoReview' => $counters->todoReview,
                'nbSelectRun' => $counters->selectRun,
                'nbSelectReview' => $counters->selectReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }
}
