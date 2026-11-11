<?php

namespace App\Controller;

use App\Form\ReportPeriodType;
use App\Service\CurrentUserProfileProvider;
use App\Service\Report\MedicalWeightReportService;
use App\Service\Report\PeriodReportService;
use App\Service\Report\ReportDataExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

final class ReportController extends AbstractController
{
    #[Route('/report', name: 'app_report')]
    public function index(
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        PeriodReportService $periodReportService
    ): Response {
        $reportPeriodForm = $this->createForm(ReportPeriodType::class);

        $reportPeriodForm->handleRequest($request);

        if ($reportPeriodForm->isSubmitted() && $reportPeriodForm->isValid()) {
            $reportPeriod = $reportPeriodForm->getData();

            $profile = $currentUserProfileProvider->getProfile();

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
                'habitSummary' => $reportPeriod['habitSummary'] ?? null,
                'medicalNotes' => $reportPeriod['medicalNotes'] ?? null,
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
        CurrentUserProfileProvider $currentUserProfileProvider,
        PeriodReportService $periodReportService,
        ReportDataExporter $reportDataExporter,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

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

    #[Route('/report/pdf/{startDate}/{endDate}', name: 'app_report_pdf', methods: ['GET', 'POST'])]
    public function exportPdf(
        string $startDate,
        string $endDate,
        Request $request,
        CurrentUserProfileProvider $currentUserProfileProvider,
        PeriodReportService $periodReportService,
        MedicalWeightReportService $medicalWeightReportService,
    ): Response {
        $profile = $currentUserProfileProvider->getProfile();

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

        $habitSummary = $this->validatedOptionalReportText($request->request->get('habitSummary'));
        $medicalNotes = $this->validatedOptionalReportText($request->request->get('medicalNotes'));
        $medicalReport = $medicalWeightReportService->generate(
            $profile,
            $report->food,
            $habitSummary,
            $medicalNotes,
        );

        $reportHtml = $this->renderView('report/pdf.html.twig', [
            'report' => $medicalReport,
            'foodPeriodStart' => $startDateObject,
            'foodPeriodEnd' => $endDateObject,
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

    private function validatedOptionalReportText(mixed $value): ?string
    {
        $text = trim(is_string($value) ? $value : '');
        if (mb_strlen($text) > 350) {
            throw new BadRequestHttpException('Les textes du rapport sont limités à 350 caractères.');
        }

        return $text === '' ? null : $text;
    }
}
