<?php

namespace App\Controller;

use App\Entity\Milestone;
use App\Form\MilestoneType;
use App\Pagination\PaginatedResult;
use App\Repository\MilestoneRepository;
use App\Service\CurrentUserProfileProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MilestoneController extends AbstractController
{
    #[Route('/milestone/new', name: 'app_milestone_new')]
    #[Route('/milestone/{id}/edit', name: 'app_milestone_edit', requirements: ['id' => '\d+'])]
    public function createOrEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        CurrentUserProfileProvider $currentUserProfileProvider,
        ?Milestone $milestone = null,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        $isEditMode = $milestone !== null;

        if ($isEditMode && !$currentUserProfileProvider->ownsProfile($milestone->getProfile())) {
            throw $this->createNotFoundException();
        }

        if ($milestone === null) {
            $milestone = new Milestone();
            $milestone->setProfile($profile);
        }

        $form = $this->createForm(MilestoneType::class, $milestone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($milestone);
            $entityManager->flush();

            return $this->redirectToRoute($isEditMode ? 'app_milestone_show' : 'app_dashboard', $isEditMode ? ['id' => $milestone->getId()] : []);
        }

        return $this->render('milestone/new.html.twig', [
            'form' => $form,
            'editMode' => $isEditMode,
        ]);
    }

    #[Route('/milestones', name: 'app_milestone_index')]
    public function index(
        Request $request,
        MilestoneRepository $milestoneRepository,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getRequiredProfile();
        $ongoingMilestonesPagination = $milestoneRepository->paginateForProfileByCompletionStatus(
            $profile,
            false,
            $request->query->getInt('ongoingPage', 1),
            PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
        );
        $achievedMilestonesPagination = $milestoneRepository->paginateForProfileByCompletionStatus(
            $profile,
            true,
            $request->query->getInt('achievedPage', 1),
            PaginatedResult::DEFAULT_ITEMS_PER_PAGE,
        );

        return $this->render('milestone/index.html.twig', [
            'ongoingMilestonesPagination' => $ongoingMilestonesPagination,
            'achievedMilestonesPagination' => $achievedMilestonesPagination,
        ]);
    }

    #[Route('/milestone/{id}', name: 'app_milestone_show', requirements: ['id' => '\d+'])]
    public function show(Milestone $milestone, CurrentUserProfileProvider $currentUserProfileProvider): Response
    {
        if (!$currentUserProfileProvider->ownsProfile($milestone->getProfile())) {
            throw $this->createNotFoundException();
        }

        return $this->render('milestone/show.html.twig', [
            'milestone' => $milestone,
        ]);
    }
}
