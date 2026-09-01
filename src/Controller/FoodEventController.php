<?php

namespace App\Controller;

use App\Entity\FoodEvent;
use App\Form\FoodEventType;
use App\Repository\FoodEventRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FoodEventController extends AbstractController
{
    #[Route('/food/new', name: 'app_food_new')]
    #[Route('/food/new/{id}', name: 'app_food_edit')]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
        ?FoodEvent $foodEvent = null,
    ): Response {
        $isEditMode = $foodEvent !== null;
        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($foodEvent?->getProfile())) { throw $this->createNotFoundException(); }

        if ($foodEvent === null) {
            $foodEvent = new FoodEvent();

            $foodEvent
                ->setProfile($currentUserProfileProvider->getRequiredProfile())
                ->setEatenAt(new \DateTimeImmutable());
        }

        $form = $this->createForm(FoodEventType::class, $foodEvent);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($foodEvent);
            $entityManager->flush();

            if ($isEditMode) {
                return $this->redirectToRoute('app_food_index');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('food_event/new.html.twig', [
            'form' => $form,
            'editMod' => $isEditMode,
        ]);
    }

    #[Route('/food/show/{id}', name: 'app_food_show')]
    public function show(FoodEvent $foodEvent, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($foodEvent->getProfile())) { throw $this->createNotFoundException(); }
        return $this->render('food_event/show.html.twig', [
            'foodEvent' => $foodEvent,
        ]);
    }

    #[Route('/food', name: 'app_food_index')]
    public function index(FoodEventRepository $foodEventRepository, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        $foodEvents = $foodEventRepository->findAllForProfileOrderedFromNewest($currentUserProfileProvider->getRequiredProfile());

        return $this->render('food_event/index.html.twig', [
            'foodEvents' => $foodEvents,
        ]);
    }
}
