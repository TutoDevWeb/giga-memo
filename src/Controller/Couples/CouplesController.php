<?php

namespace App\Controller\Couples;

use App\Controller\Trait\JsonCsrfTokenTrait;
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
    use JsonCsrfTokenTrait;

    /**
     * Ce contrôleur sert à afficher la liste des couples qr d'une faq.
     */
    #[Route('/couples/list-by-faq/{id_faq<\d+>}', name: 'app_couples_list_by_faq')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function list_by_faq(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
        CouplesRepository $couplesRepository,
    ): Response {
        return $this->render('couples/list-by-faq.html.twig', [
            'faq' => $faq,
            'couples' => $couplesRepository->findByFaqWithImagesAndRules($faq),
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

        // On récupère l'utilisateur connecté et on l'injecte dans l'entité
        /** @var Users $user */
        $user = $this->getUser();
        $couple->setUser($user);

        $form = $this->createForm(CoupleFormType::class, $couple, ['faq' => $faq]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $couple->setPendingForRun(true);
            $couple->setPendingForReview(true);
            $couple->setFlaggedForReview(false);

            $entityManager->persist($couple);
            $entityManager->flush();

            // On récupère les images
            $images = $form->get('images')->getData();

            // On les passe au service
            $skippedForQuota = $pictureService->upload($entityManager, $couple, $images, $user);

            if ($skippedForQuota > 0) {
                $this->addFlash('warning', sprintf('%d image(s) n\'ont pas été ajoutées : quota d\'images atteint pour votre compte.', $skippedForQuota));
            }

            $this->addFlash('success', 'La QR a été créée avec succès !');

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
        CouplesRepository $couplesRepository
    ): Response {

        $idToUpdate = $couple->getId();

        $form = $this->createForm(CoupleFormType::class, $couple, [
            'from' => $from,
            'faq' => $faq,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // La cuisine ci-dessous c'est pour réordonner les positions des couples dans la liste
            $newNum = $form->getData()->getNum();

            $couples = $couplesRepository->findBy(
                ['faq' => $faq],
                ['num' => 'ASC']
            );

            $num = 1;
            $oldNum = 0;
            // D'abord j'arrange pour que les num soient consécutifs
            // C'est pas forcément le cas pour des raisons historiques.
            // J'ai mis un num et au départ je ne l'ai pas géré
            // Donc il existe des listes où c'est pas bon.
            foreach ($couples as $c) {
                // Je détermine la position réelle de celui que je modifie
                if ($c->getId() === $idToUpdate) {
                    $oldNum = $num;
                }
                $c->setNum($num);
                $num++;
            }

            // Si une renumérotation est nécessaire
            if ($oldNum !== $newNum) {

                // Si la position demandé est plus petite que l'actuelle
                if ($newNum < $oldNum) {

                    // Parcourir de $newNum+1 et décaler de +1
                    $num = 1;
                    foreach ($couples as $c) {

                        if ($c->getId() ===  $idToUpdate) {
                            $c->setNum($newNum);
                            continue;
                        }
                        if ($num >= $newNum) {
                            dump('Set num+1');
                            $c->setNum($num + 1);
                        }
                        $num++;
                    }
                }


                // Si la position demandée est plus grande que l'actuelle
                if ($newNum > $oldNum) {

                    $num = 1;
                    foreach ($couples as $c) {

                        if ($c->getId() ===  $idToUpdate) {
                            $c->setNum($newNum);
                            continue;
                        }
                        // Renuméroter tout jusqu'à < $newNum
                        if ($num < $newNum) {
                            $c->setNum($num);
                        }
                        $num++;
                    }
                }

                foreach ($couples as $c) {
                    $entityManager->persist($c);
                }
            } else {
                $entityManager->persist($couple);
            }

            $entityManager->flush();

            // On récupère les images
            $images = $form->get('images')->getData();

            /** @var Users $user */
            $user = $this->getUser();

            // On les passe au service
            $skippedForQuota = $pictureService->upload($entityManager, $couple, $images, $user);

            if ($skippedForQuota > 0) {
                $this->addFlash('warning', sprintf('%d image(s) n\'ont pas été ajoutées : quota d\'images atteint pour votre compte.', $skippedForQuota));
            }

            $id_faq = $faq->getId();

            $this->addFlash('success', 'La QR a été modifiée avec succès !');

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

            $this->addFlash('success', 'La QR a été supprimée avec succès !');
        }

        return $this->redirectToRoute('app_couples_list_by_faq', ['id_faq' => $faq->getId()]);
    }

    /**
     * Click sur bouton 'Revoir' lorsque l'on déroule la liste en run. 
     */
    #[Route('/couples/set-one-review/{id_couple<\d+>}', name: 'app_couples_set_one_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function set_one_review(
        EntityManagerInterface $entityManager,
        CouplesRepository $repo,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        Request $request,
    ): Response {
        $token = $this->getCsrfTokenFromJson($request);

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('set-one-review' . $couple->getId(), $token)) {
            // On le met dans la sélection
            $couple->setFlaggedForReview(true);
            // Du coup il est à faire
            $couple->setPendingForReview(true);

            $entityManager->persist($couple);
            $entityManager->flush();

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $counters = $repo->countAll($couple->getFaq());

            return new JsonResponse([
                'nbRemainingToRun' => $counters->remainingToRun,
                'nbRemainingToReview' => $counters->remainingToReview,
                'nbTotalToRun' => $counters->totalToRun,
                'nbTotalToReview' => $counters->totalToReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }

    /**
     * Click sur bouton 'Ne plus Revoir' lorsque l'on déroule la liste des review. 
     */
    #[Route('/couples/cancel-one-review/{id_couple<\d+>}', name: 'app_couples_cancel_one_review')]
    #[IsGranted(ResourceOwnerVoter::EDIT, subject: 'couple')]
    public function cancel_one_review(
        EntityManagerInterface $entityManager,
        CouplesRepository $repo,
        #[MapEntity(id: 'id_couple')] Couples $couple,
        Request $request,
    ): Response {
        $token = $this->getCsrfTokenFromJson($request);

        // On teste pour savoir si le token est valide.
        if ($this->isCsrfTokenValid('cancel-one-review' . $couple->getId(), $token)) {
            // On l'enlève de la sélection Review
            $couple->setFlaggedForReview(false);

            // Du coup il n'est plus à faire
            $couple->setPendingForReview(false);
            $entityManager->persist($couple);
            $entityManager->flush();

            // Faire les comptes et retourner les valeurs des indicateurs pour maj affichage
            $counters = $repo->countAll($couple->getFaq());

            return new JsonResponse([
                'nbRemainingToRun' => $counters->remainingToRun,
                'nbRemainingToReview' => $counters->remainingToReview,
                'nbTotalToRun' => $counters->totalToRun,
                'nbTotalToReview' => $counters->totalToReview,
            ]);
        }

        return new JsonResponse(['message' => 'KO']);
    }
}
