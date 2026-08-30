<?php

namespace App\Controller;

use App\Entity\WeightEntry;
use App\Form\WeightEntryType;
use App\Repository\WeightEntryRepository;
use App\Service\CurrentUserProfileProvider;
use App\Service\Milestone\MilestoneService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WeightEntryController extends AbstractController
{
    #[Route('/weight/new', name: 'app_weight_new')]
    #[Route('/weight/new/{id}', name: 'app_weight_edit')]
    public function createOrEdit(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        EntityManagerInterface $entityManager,
        MilestoneService $milestoneService,
        ?WeightEntry $weightEntry = null,
    ): Response {
        $isEditMode = $weightEntry !== null;

        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($weightEntry?->getProfile())) { throw $this->createNotFoundException(); }

        if ($weightEntry === null) {
            $weightEntry = new WeightEntry();
        }

        $form = $this->createForm(
            WeightEntryType::class,
            $weightEntry
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isEditMode) {
                $weightEntry->setProfile($currentUserProfileProvider->getRequiredProfile());
            }

            $profile = $weightEntry->getProfile();
            $recordedWeight = $weightEntry->getWeight();

            if ($profile === null || $recordedWeight === null) {
                throw new \LogicException('A weight entry requires a profile and a weight.');
            }

            $milestoneService->validateReachedWeightMilestones(
                $profile,
                $recordedWeight,
                $weightEntry->getMeasuredAt(),
            );

            $entityManager->persist($weightEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('weight_entry/new.html.twig', [
            'form' => $form,
            'editMod' => $isEditMode,
        ]);
    }

    #[Route('/weight', name: 'app_weight_index')]
    public function index(WeightEntryRepository $weightEntryRepository, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        $weightEntries = $weightEntryRepository->findAllForProfile($currentUserProfileProvider->getRequiredProfile());
        return $this->render('weight_entry/index.html.twig', [
            'controller_name' => 'WeightEntryController',
            'weightEntries' => $weightEntries,
        ]);
    }

    #[Route('/weight/show/{id}', name: 'app_weight_show')]
    public function show(WeightEntry $weightEntry, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($weightEntry->getProfile())) { throw $this->createNotFoundException(); }
        return $this->render('weight_entry/show.html.twig', [
            'weightEntry' => $weightEntry,
        ]);
    }
}
