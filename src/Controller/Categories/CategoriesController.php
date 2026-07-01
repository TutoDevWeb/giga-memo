<?php

namespace App\Controller\Categories;

use App\Entity\Categories;
use App\Entity\Users;
use App\Form\CategoriesType;
use App\Repository\CategoriesRepository;
use App\Security\Voter\ResourceOwnerVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categories')]
final class CategoriesController extends AbstractController
{
    #[Route(name: 'app_categories_list', methods: ['GET'])]
    public function list(CategoriesRepository $categoriesRepository): Response
    {


        // On récupère l'utilisateur connecté
        $user = $this->getUser();

        // Sécurité optionnelle : si tu veux bloquer l'accès aux utilisateurs anonymes
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à vos catégories.');
        }

        return $this->render('categories/list.html.twig', [
            'ariane' => ['index' => true, 'category' => true],
            'categories' => $categoriesRepository->findBy(['user' => $user]),
        ]);
    }
    #[Route('/new', name: 'app_categories_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Categories();
        $form = $this->createForm(CategoriesType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var Users $user */
            $user = $this->getUser();

            // Maintenant PHPStan sait à 100% que $user est une instance de Users
            $category->setUser($user);

            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_categories_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categories/new.html.twig', [
            'ariane' => ['index' => true, 'category' => true, 'create' => 'category'],
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categories_show', methods: ['GET'])]
    public function show(Categories $category): Response
    {
        return $this->render('categories/show.html.twig', [
            'ariane' => ['index' => true, 'category' => true],
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categories_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categories $category, EntityManagerInterface $entityManager): Response
    {

        // 🔒 Sécurisation : Si l'user connecté n'est pas le proprio, Symfony balance une 403 Access Denied
        $this->denyAccessUnlessGranted(ResourceOwnerVoter::EDIT, $category);

        $form = $this->createForm(CategoriesType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_categories_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categories/edit.html.twig', [
            'ariane' => ['index' => true, 'category' => true, 'update' => 'category'],
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categories_delete', methods: ['POST'])]
    public function delete(Request $request, Categories $category, EntityManagerInterface $entityManager): Response
    {

        // 🔒 Sécurisation : Si l'user connecté n'est pas le proprio, Symfony balance une 403 Access Denied
        $this->denyAccessUnlessGranted(ResourceOwnerVoter::DELETE, $category);

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_categories_list', [], Response::HTTP_SEE_OTHER);
    }
}
