<?php

namespace App\Controller;

use App\Form\ReportPeriodType;
use App\Repository\ProfileRepository;
use App\Service\Report\PeriodReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Report\ReportDataExporter;

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

    #[Route('/report/data/{startDate}/{endDate}', name: 'app_report_data')]
    public function exportData(
        string $startDate,
        string $endDate,
        ProfileRepository $profileRepository,
        PeriodReportService $periodReportService,
        ReportDataExporter $reportDataExporter,
    ): Response {
        $profile = $profileRepository->findOneBy([]);

        if ($profile === null) {
            throw $this->createNotFoundException('Aucun profil trouvé.');
        }

        $startDateObject = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $startDate,
        );

        $endDateObject = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $endDate,
        );

        if ($startDateObject === false || $endDateObject === false) {
            throw $this->createNotFoundException('Période invalide.');
        }

        $report = $periodReportService->generate(
            $profile,
            $startDateObject,
            $endDateObject,
        );

        $json = $reportDataExporter->export($report);

        return new Response(
            $json,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="rapport.json"',
            ],
        );
    }

}
