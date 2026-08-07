<?php

namespace App\Controller;

use App\Entity\FoodEvent;
use App\Entity\Profile;
use App\Form\FoodEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FoodEventController extends AbstractController
{
    #[Route('/food/new', name: 'app_food_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $entityManager
            ->getRepository(Profile::class)
            ->findOneBy([]);

        $foodEvent = new FoodEvent();

        $foodEvent
            ->setProfile($profile)
            ->setEatenAt(new \DateTimeImmutable());

        $form = $this->createForm(FoodEventType::class, $foodEvent);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($foodEvent);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('food_event/new.html.twig', [
            'form' => $form,
        ]);
    }
}