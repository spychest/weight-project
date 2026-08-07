<?php

namespace App\Controller;

use App\Entity\DrinkEntry;
use App\Entity\Profile;
use App\Form\DrinkEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DrinkEntryController extends AbstractController
{
    #[Route('/drink/new', name: 'app_drink_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $profile = $entityManager
            ->getRepository(Profile::class)
            ->findOneBy([]);

        $drinkEntry = new DrinkEntry();

        $drinkEntry
            ->setProfile($profile)
            ->setDate(new \DateTimeImmutable());

        $form = $this->createForm(DrinkEntryType::class, $drinkEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($drinkEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('drink_entry/new.html.twig', [
            'form' => $form,
        ]);
    }
}