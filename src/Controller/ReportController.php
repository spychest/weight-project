<?php

namespace App\Controller;

use App\Form\ReportPeriodType;
use App\Repository\ProfileRepository;
use App\Service\Report\PeriodReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    #[Route('/report', name: 'app_report')]
    public function index(
        Request $request,
        ProfileRepository $profileRepository,
        PeriodReportService $periodReportService
    ): Response {
        $form = $this->createForm(ReportPeriodType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $profile = $profileRepository->findOneBy([]);

            $report = $periodReportService->generate(
                $profile,
                $data['startDate'],
                $data['endDate'],
            );

            return $this->render('report/result.html.twig', [
                'report' => $report,
            ]);
        }

        return $this->render('report/index.html.twig', [
            'form' => $form,
        ]);
    }


}
