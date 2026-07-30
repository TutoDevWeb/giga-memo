<?php

namespace App\Controller\Couples;

use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Form\CoupleFormType;
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


class CouplesController extends AbstractController
{
    /**
     * Ce contrôleur sert à afficher la liste des couples qr d'une faq.
     */
    #[Route('/couples/list-by-faq/{id_faq<\d+>}', name: 'app_couples_list_by_faq')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function list_by_faq(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        return $this->render('couples/list-by-faq.html.twig', [
            'faq' => $faq,
        ]);
    }

    /**
     * Ce contrôleur sert à créer un nouveau couple qr.
     */
    #[Route('/couples/new/{id_faq<\d+>}', name: 'app_couples_new')]
    #[IsGranted(ResourceOwnerVoter::NEW, subject: 'faq')]
    public function new(
        EntityManagerInterface $entityManager,
        PictureService $pictureService,
        Request $request,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {
        $nbCouple = count($faq->getCouples());

        $couple = new Couples();
        $couple->setFaq($faq);
        $couple->setNum($nbCouple + 1);

        $form = $this->createForm(CoupleFormType::class, $couple, ['layerType' => $couple->getFaq()->getLayerType(), 'faq' => $faq]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Users $user */
            $user = $this->getUser();

            // 🔒 On récupère l'utilisateur connecté et on l'injecte dans l'entité
            $couple->setUser($user);

            /*
             * Il faut refaire le set de la Faq car le champ faq est disabled dans le formulaire
             * Du coup on perd la valeur dans le $form->handleRequest()
             */
            $couple->setFaq($faq);
            $couple = $form->getData();
            $couple->setTodoRun(true);
            $couple->setTodoReview(true);
            $couple->setSelectReview(false);

            $entityManager->persist($couple);
            $entityManager->flush();

            // On récupère les images
            $images = $form->get('images')->getData();

            // On les passe au service
            $pictureService->upload($entityManager, $couple, $images, $user);

            return $this->redirectToRoute('app_couples_list_by_faq', ['id_faq' => $faq->getId()]);
        }

        return $this->render('couples/new.html.twig', [
            'form' => $form,
            'faq' => $faq,
            'formType' => 'new',
        ]);
    }

    /**
     * Ce contrôleur sert à modifier un couple qr.
     */
    #[Route('/couples/update/{from<run|review|list>}/{id_faq<\d+>}/{id_couple<\d+>}', name: 'app_couples_update')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function update(
        EntityManagerInterface $entityManager,
        Request $request,
        string $from,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        PictureService $pictureService,
    ): Response {

        $form = $this->createForm(CoupleFormType::class, $couple, [
            'layerType' => $faq->getLayerType(),
            'from' => $from,
            'faq' => $faq,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $couple->setFaq($faq);
            $couple = $form->getData();

            $entityManager->persist($couple);
            $entityManager->flush();

            // On récupère les images
            $images = $form->get('images')->getData();

            /** @var Users $user */
            $user = $this->getUser();

            // On les passe au service
            $pictureService->upload($entityManager, $couple, $images, $user);

            $id_faq = $faq->getId();

            if ('review' == $from) {
                return $this->redirectToRoute('app_faqs_review', ['id_faq' => $id_faq]);
            }

            if ('run' == $from) {
                return $this->redirectToRoute('app_faqs_run', ['id_faq' => $id_faq]);
            }

            if ('list' == $from) {
                return $this->redirectToRoute('app_couples_list_by_faq', ['id_faq' => $id_faq]);
            }
        }

        return $this->render('couples/edit.html.twig', [
            'form' => $form,
            'couple' => $couple,
            'faq' => $faq,
            'formType' => 'update',
        ]);
    }

    /**
     * Ce contrôleur sert à supprimer un couple qr.
     */
    #[Route('/couples/delete/{id_faq<\d+>}/{id_couple<\d+>}', name: 'app_couples_delete')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'faq')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function delete(
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        Request $request,
    ): Response {

        if ($this->isCsrfTokenValid('delete' . $couple->getId(), $request->getPayload()->getString('_token'))) {

            // remove du couple => remove des images car orphanremoval => suppression physique sur eventListener
            $entityManager->remove($couple);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_couples_list_by_faq', ['id_faq' => $faq->getId()]);
    }

    /**
     * Ce contrôleur sert à enregister un couple qr dans la sélection selectReview
     * Il est appelé lorsqu'un utilisateur appuie sur le bouton A Revoir en Run-normal.
     */
    #[Route('/couples/set-one-review/{id_couple<\d+>}', name: 'app_couples_set_one_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function set_one_review(
        EntityManagerInterface $entityManager,
        CouplesRepository $repo,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        Request $request,
    ): Response {
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('set-one-review' . $couple->getId(), $token)) {
            // On le met dans la sélection
            $couple->setSelectReview(true);
            // Du coup il est à faire
            $couple->setTodoReview(true);

            $entityManager->persist($couple);
            $entityManager->flush();

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $nbTodoRun = $repo->countTodoRun($couple->getFaq());
            $nbTodoReview = $repo->countTodoReview($couple->getFaq());
            $nbSelectRun = $repo->countSelectRun($couple->getFaq());
            $nbSelectReview = $repo->countSelectReview($couple->getFaq());

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
     * Ce contrôleur sert à enlever un couple qr de la sélection selectReview
     * Il est appelé lorsqu'un utilisateur appuie sur le bouton Ne plus revoir en Run-Review.
     */
    #[Route('/couples/cancel-one-review/{id_couple<\d+>}', name: 'app_couples_cancel_one_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function cancel_one_review(
        EntityManagerInterface $entityManager,
        CouplesRepository $repo,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        Request $request,
    ): Response {
        $data = json_decode($request->getContent(), true);
        $token = $data['_token'];

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('cancel-one-review' . $couple->getId(), $token)) {
            // On l'enlève de la sélection Review
            $couple->setSelectReview(false);

            // Du coup il n'est plus à faire
            $couple->setTodoReview(false);
            $entityManager->persist($couple);
            $entityManager->flush();

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $nbTodoRun = $repo->countTodoRun($couple->getFaq());
            $nbTodoReview = $repo->countTodoReview($couple->getFaq());
            $nbSelectRun = $repo->countSelectRun($couple->getFaq());
            $nbSelectReview = $repo->countSelectReview($couple->getFaq());

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
