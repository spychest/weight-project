<?php

namespace App\Controller;

use App\Entity\FoodEvent;
use App\Entity\Profile;
use App\Form\FoodEventType;
use App\Repository\FoodEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FoodEventController extends AbstractController
{
    #[Route('/food/new', name: 'app_food_new')]
    #[Route('/food/new/{id}', name: 'app_food_edit')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ?FoodEvent $foodEvent = null
    ): Response {
        $editMod = true;
        if($foodEvent == null){
            $editMod = false;
            $profile = $entityManager
                ->getRepository(Profile::class)
                ->findOneBy([]);

            $foodEvent = new FoodEvent();

            $foodEvent
                ->setProfile($profile)
                ->setEatenAt(new \DateTimeImmutable());
        }


        $form = $this->createForm(FoodEventType::class, $foodEvent);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($foodEvent);
            $entityManager->flush();

            if($editMod !== false) {
                return $this->redirectToRoute('app_food_index');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('food_event/new.html.twig', [
            'form' => $form,
            'editMod' => $editMod,
        ]);
    }

    #[Route('/food/show/{id}', name: 'app_food_show')]
    public function show(FoodEvent $foodEvent): Response
    {
        return $this->render('food_event/show.html.twig', [
            'foodEvent' => $foodEvent,
        ]);
    }

    #[Route('/food', name: 'app_food_index')]
    public function index(FoodEventRepository $foodEventRepository): Response
    {
        $foodEvents = $foodEventRepository->findBy([], [
            'eatenAt' => 'DESC',
        ]);

        return $this->render('food_event/index.html.twig', [
            'foodEvents' => $foodEvents,
        ]);
    }
}