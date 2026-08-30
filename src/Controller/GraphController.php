<?php

namespace App\Controller;

use App\Service\CurrentUserProfileProvider;
use App\Service\GraphService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GraphController extends AbstractController
{
    #[Route('/graph', name: 'app_graph')]
    public function index(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/index.html.twig', [
            'weightGraphData' => $graphService->getWeightGraphData($profile),
            'hydrationGraphData' => $graphService->getHydrationGraphData($profile),
            'sleepGraphData' => $graphService->getSleepGraphData($profile),
            'mealTypeGraphData' => $graphService->getMealTypeGraphData($profile),
            'dailyCheckinGraphData' => $graphService->getDailyCheckinGraphData($profile),
        ]);
    }

    #[Route('/graph/weight', name: 'app_graph_weight')]
    public function weight(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/weight.html.twig', [
            'weightData' => $graphService->getWeightDetailData($profile),
        ]);
    }

    #[Route('/graph/hydration', name: 'app_graph_hydration')]
    public function hydration(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/hydration.html.twig', [
            'hydrationData' => $graphService->getHydrationDetailData($profile),
        ]);
    }

    #[Route('/graph/sleep', name: 'app_graph_sleep')]
    public function sleep(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/sleep.html.twig', [
            'sleepData' => $graphService->getSleepDetailData($profile),
        ]);
    }

    #[Route('/graph/meal', name: 'app_graph_meal')]
    public function meal(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/meal.html.twig', [
            'mealData' => $graphService->getMealDetailData($profile),
        ]);
    }

    #[Route('/graph/checkin', name: 'app_graph_checkin')]
    public function checkin(
        GraphService $graphService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('graph/checkin.html.twig', [
            'checkinData' => $graphService->getDailyCheckinDetailData($profile),
        ]);
    }
}
