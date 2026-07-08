<?php

namespace App\Controller\Faqs;

use App\Entity\Faqs;
use App\Form\FaqFormType;
use App\Repository\CouplesRepository;
use App\Security\Voter\ResourceOwnerVoter;
use App\Service\PictureService;
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
    /**
     * Ce controleur a pour but de créer une nouvelle Faq
     * Il est appelé lorsqu'un utilisateur clique sur le bouton Créer une Faq du Mode-Edit.
     */
    #[Route('/faqs/new', name: 'app_faqs_new')]
    public function new(EntityManagerInterface $entityManager, Request $request): Response
    {
        $faq = new Faqs();

        $form = $this->createForm(FaqFormType::class, $faq, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq = $form->getData();

            // 🔒 On récupère l'utilisateur connecté et on l'injecte dans l'entité
            $faq->setUser($this->getUser());

            // On vérifie que la catégorie parente appartient bien à l'utilisateur connecté.
            $this->denyAccessUnlessGranted(ResourceOwnerVoter::NEW, $faq->getCategory());

            $entityManager->persist($faq);
            $entityManager->flush();

            return $this->redirectToRoute('app_main_index');
        }

        return $this->render('faqs/new.html.twig', [
            'ariane' => ['index' => true, 'faq' => true, 'create' => true],
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

            return $this->redirectToRoute('app_main_index', ['id_faq' => $faq->getId()]);
        }

        return $this->render('faqs/update.html.twig', [
            'ariane' => ['index' => true, 'edit' => true, 'update' => 'faq'],
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
        }

        return $this->redirectToRoute('app_main_index');
    }

    /**
     * Ce contrôleur a pour but de dérouler la liste de tous les QRs qui sont dans une Faq.
     * Pour ça l'utilisateur appuie sur le bouton Run du Mode-Run
     * Ensuite il appuie sur le bouton Suivant.
     */
    #[Route('/faqs/run/{id_faq<\d+>}', name: 'app_faqs_run')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function run(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $couple = $repo->findNextSelectRun($faq);

        $nbTodoRun = $repo->countTodoRun($faq);
        $nbTodoReview = $repo->countTodoReview($faq);
        $nbSelectRun = $repo->countSelectRun($faq);
        $nbSelectReview = $repo->countSelectReview($faq);

        return $this->render('faqs/run.html.twig', [
            'ariane' => ['index' => true, 'run' => true, 'mode_run' => 'normal'],
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
    #[Route('/faqs/review/{id_faq<\d+>}', name: 'app_faqs_review')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function review(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $couple = $repo->findNextSelectReview($faq);

        $nbTodoRun = $repo->countTodoRun($faq);
        $nbTodoReview = $repo->countTodoReview($faq);
        $nbSelectRun = $repo->countSelectRun($faq);
        $nbSelectReview = $repo->countSelectReview($faq);

        return $this->render('faqs/review.html.twig', [
            'ariane' => ['index' => true, 'run' => true, 'mode_run' => 'review'],
            'faq' => $faq,
            'couple' => $couple,
            'nbTodoRun' => $nbTodoRun,
            'nbTodoReview' => $nbTodoReview,
            'nbSelectRun' => $nbSelectRun,
            'nbSelectReview' => $nbSelectReview,
        ]);
    }

    #[Route('/faqs/next-run/{id_faq<\d+>}', name: 'app_faqs_next_run')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function nextRun(
        CouplesRepository $repo,
        EntityManagerInterface $em,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
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
        return $this->redirectToRoute('app_faqs_run', ['id_faq' => $faq->getId()]);
    }

    #[Route('/faqs/next-review/{id_faq<\d+>}', name: 'app_faqs_next_review')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function nextReview(
        CouplesRepository $repo,
        EntityManagerInterface $em,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $couple = $repo->findNextSelectReview($faq);

        if (null !== $couple) {
            $couple->setTodoReview(false);
            $em->flush();
        }

        // On a fini la faq.
        if (0 == $repo->countTodoReview($faq)) {
            $repo->restartTodoReview($faq);
        }
        $em->flush();

        // On redirige
        return $this->redirectToRoute('app_faqs_review', ['id_faq' => $faq->getId()]);
    }

    /**
     * Ce contrôleur est appelé lorsqu'un utilisateur appuie sur le bouton Restart
     * Il met à 1 tous les booléens todoRun et todoReview de la Faqs passée en argument.
     */
    #[Route('/faqs/restart/{id_faq<\d+>}', name: 'app_faqs_restart')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function restart(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
    ): Response {
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
    #[Route('/faqs/reset-review/{id_faq<\d+>}', name: 'app_faqs_reset_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    public function reset_review(
        CouplesRepository $repo,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        Request $request,
    ): Response {
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
