<?php

namespace App\Controller;

use App\Service\Dashboard\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(DashboardService $dashboardService): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'dashboard' => $dashboardService->getDashboard(),
        ]);
    }
}
