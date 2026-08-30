<?php

namespace App\Controller;

use App\Service\CurrentUserProfileProvider;
use App\Service\Dashboard\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser() !== null) { return $this->redirectToRoute('app_dashboard'); }
        return $this->render('home/index.html.twig');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DashboardService $dashboardService,
        CurrentUserProfileProvider $currentUserProfileProvider,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

        if ($profile === null) {
            return $this->redirectToRoute('app_profile_new');
        }

        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $dashboardService->getDashboardForProfile($profile),
        ]);
    }
}
