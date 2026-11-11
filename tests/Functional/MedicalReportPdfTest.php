<?php

namespace App\Tests\Functional;

use App\DTO\Report\MedicalWeightReportData;
use App\Kernel;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

final class MedicalReportPdfTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function completeMedicalReportFitsOnOneA4Page(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $chart = '<svg xmlns="http://www.w3.org/2000/svg" width="760" height="300">'
            .'<rect width="760" height="300" fill="#fff"/>'
            .'<polyline points="20,30 180,70 360,120 550,170 740,230" fill="none" stroke="#17664f"/>'
            .'</svg>';
        $maximumLengthText = str_repeat('Évolution documentée et information utile. ', 8);
        $report = new MedicalWeightReportData(
            generatedAt: new \DateTimeImmutable('2026-11-11'),
            trackingStartDate: new \DateTimeImmutable('2026-01-01'),
            trackingEndDate: new \DateTimeImmutable('2026-10-31'),
            age: 36,
            height: 178.0,
            startingWeight: 114.0,
            startingBmi: 35.98,
            latestWeight: 95.5,
            currentBmi: 30.14,
            latestWeightDate: new \DateTimeImmutable('2026-10-31'),
            latestWeightIsDeclared: false,
            totalChange: -18.5,
            totalChangePercentage: -16.2,
            averageWeeklyChange: -0.43,
            trackingDurationDays: 303,
            previousMeasurementChange: -0.4,
            thirtyDayChange: -1.7,
            measurementCount: 48,
            invalidMeasurementCount: 1,
            chartDataUri: 'data:image/svg+xml;base64,'.base64_encode($chart),
            fourWeekTrend: -1.59,
            eightWeekTrend: -1.64,
            allTimeTrend: -1.8,
            foodEntryCount: 54,
            averageHungerLevel: 4.2,
            averagePleasureLevel: 7.6,
            habitSummary: mb_substr($maximumLengthText, 0, 350),
            medicalNotes: mb_substr($maximumLengthText, 0, 350),
        );

        $html = $twig->render('report/pdf.html.twig', [
            'report' => $report,
            'foodPeriodStart' => new \DateTimeImmutable('2026-09-01'),
            'foodPeriodEnd' => new \DateTimeImmutable('2026-10-31'),
        ]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        self::assertSame(1, $pdf->getCanvas()->get_page_count());
        self::assertStringContainsString('/MediaBox [0.000 0.000 595.280 841.890]', $pdf->output());
    }
}
