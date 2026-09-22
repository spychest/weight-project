<?php

namespace App\Controller;

use App\Entity\FavoriteMeal;
use App\Form\FavoriteMealType;
use App\Repository\FavoriteMealRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/food/favorites')]
final class FavoriteMealController extends AbstractController
{
    #[Route('', name: 'app_favorite_meal_index', methods: ['GET'])]
    public function index(
        FavoriteMealRepository $favoriteMealRepository,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        return $this->render('favorite_meal/index.html.twig', [
            'favoriteMeals' => $favoriteMealRepository->findForProfile($currentUserProfileProvider->getRequiredProfile()),
        ]);
    }

    #[Route('/new', name: 'app_favorite_meal_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_favorite_meal_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function createOrEdit(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
        ?FavoriteMeal $favoriteMeal = null,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();
        $isEditMode = $favoriteMeal !== null;

        if ($favoriteMeal !== null && $favoriteMeal->getProfile() !== $profile) {
            throw $this->createNotFoundException();
        }

        $favoriteMeal ??= (new FavoriteMeal())->setProfile($profile);
        $form = $this->createForm(FavoriteMealType::class, $favoriteMeal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($favoriteMeal);
            $entityManager->flush();
            $this->addFlash('success', $isEditMode ? 'Le repas favori a été modifié.' : 'Le repas favori a été ajouté.');

            return $this->redirectToRoute('app_favorite_meal_index');
        }

        return $this->render('favorite_meal/form.html.twig', [
            'form' => $form,
            'favoriteMeal' => $favoriteMeal,
            'editMode' => $isEditMode,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_favorite_meal_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        FavoriteMeal $favoriteMeal,
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($favoriteMeal->getProfile() !== $currentUserProfileProvider->getRequiredProfile()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('delete-favorite-meal-'.$favoriteMeal->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $entityManager->remove($favoriteMeal);
        $entityManager->flush();
        $this->addFlash('success', 'Le repas favori a été supprimé.');

        return $this->redirectToRoute('app_favorite_meal_index');
    }
}
