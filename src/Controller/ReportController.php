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
use Dompdf\Dompdf;
use Dompdf\Options;

final class ReportController extends AbstractController
{
    #[Route('/report', name: 'app_report')]
    public function index(
        Request $request,
        ProfileRepository $profileRepository,
        PeriodReportService $periodReportService
    ): Response {
        $reportPeriodForm = $this->createForm(ReportPeriodType::class);

        $reportPeriodForm->handleRequest($request);

        if ($reportPeriodForm->isSubmitted() && $reportPeriodForm->isValid()) {
            $reportPeriod = $reportPeriodForm->getData();

            $profile = $profileRepository->findFirstProfile();

            if ($profile === null) {
                return $this->redirectToRoute('app_profile_new');
            }

            $report = $periodReportService->generate(
                $profile,
                $reportPeriod['startDate'],
                $reportPeriod['endDate'],
            );

            return $this->render('report/result.html.twig', [
                'report' => $report,
            ]);
        }

        return $this->render('report/index.html.twig', [
            'form' => $reportPeriodForm,
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
        $profile = $profileRepository->findFirstProfile();

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

        $serializedReport = $reportDataExporter->export($report);

        return new Response(
            $serializedReport,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="rapport.json"',
            ],
        );
    }

    #[Route('/report/pdf/{startDate}/{endDate}', name: 'app_report_pdf')]
    public function exportPdf(
        string $startDate,
        string $endDate,
        ProfileRepository $profileRepository,
        PeriodReportService $periodReportService,
    ): Response {
        $profile = $profileRepository->findFirstProfile();

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

        $reportHtml = $this->renderView('report/pdf.html.twig', [
            'report' => $report,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $pdfDocument = new Dompdf($options);

        $pdfDocument->loadHtml($reportHtml);
        $pdfDocument->setPaper('A4', 'portrait');
        $pdfDocument->render();

        return new Response(
            $pdfDocument->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="rapport.pdf"',
            ],
        );
    }
}
