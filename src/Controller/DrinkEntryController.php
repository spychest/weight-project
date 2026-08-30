<?php

namespace App\Controller;

use App\Entity\DrinkEntry;
use App\Form\DrinkEntryType;
use App\Repository\DrinkEntryRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DrinkEntryController extends AbstractController
{
    #[Route('/drink/new', name: 'app_drink_new')]
    #[Route('/drink/new/{id}', name: 'app_drink_edit')]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
        ?DrinkEntry $drinkEntry = null,
    ): Response {
        $isEditMode = $drinkEntry !== null;
        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($drinkEntry?->getProfile())) { throw $this->createNotFoundException(); }

        if ($drinkEntry === null) {
            $drinkEntry = new DrinkEntry();

            $drinkEntry
                ->setProfile($currentUserProfileProvider->getRequiredProfile())
                ->setDate(new \DateTimeImmutable());
        }

        $form = $this->createForm(DrinkEntryType::class, $drinkEntry);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($drinkEntry);
            $entityManager->flush();
            if (!$isEditMode) {
                return $this->redirectToRoute('app_dashboard');
            }

            return $this->redirectToRoute('app_drink_index');
        }

        return $this->render('drink_entry/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/drink/show/{id}', name: 'app_drink_show')]
    public function show(DrinkEntry $drinkEntry, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($drinkEntry->getProfile())) { throw $this->createNotFoundException(); }
        return $this->render('drink_entry/show.html.twig', [
            'drinkEntry' => $drinkEntry,
        ]);
    }

    #[Route('/drink/index', name: 'app_drink_index')]
    public function index(DrinkEntryRepository $drinkEntryRepository, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        $drinkEntries = $drinkEntryRepository->findAllForProfile($currentUserProfileProvider->getRequiredProfile());

        return $this->render('drink_entry/index.html.twig', [
            'controller_name' => 'DrinkEntryController',
            'drinkEntries' => $drinkEntries,
        ]);
    }
}
