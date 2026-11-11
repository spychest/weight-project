<?php

namespace App\Service\Report;

use App\DTO\Report\MedicalWeightReportData;
use App\DTO\Report\FoodReportData;
use App\Entity\Profile;
use App\Repository\WeightEntryRepository;

final readonly class MedicalWeightReportService
{
    private const FOUR_WEEK_PERIOD_DAYS = 28;
    private const EIGHT_WEEK_PERIOD_DAYS = 56;
    private const RECENT_PERIOD_DAYS = 30;
    private const THIRTY_DAY_REFERENCE_TOLERANCE_DAYS = 7;

    public function __construct(private WeightEntryRepository $weightEntryRepository)
    {
    }

    public function generate(
        Profile $profile,
        FoodReportData $foodReport,
        ?string $habitSummary,
        ?string $medicalNotes,
        ?\DateTimeImmutable $generatedAt = null,
    ): MedicalWeightReportData {
        $generatedAt ??= new \DateTimeImmutable();
        $referenceDate = $profile->getCreatedAt();
        $startingWeight = $this->validWeight($profile->getStartingWeight());
        $validEntries = [];
        $invalidMeasurementCount = 0;

        foreach ($this->weightEntryRepository->findAllForProfile($profile) as $weightEntry) {
            $weight = $this->validWeight($weightEntry->getWeight());
            if ($weight === null) {
                $invalidMeasurementCount++;
                continue;
            }

            $validEntries[] = [
                'date' => $weightEntry->getMeasuredAt(),
                'weight' => $weight,
                'initial' => false,
            ];
        }

        usort(
            $validEntries,
            static fn (array $left, array $right): int => $left['date'] <=> $right['date'],
        );

        $chartPoints = $validEntries;
        if ($startingWeight !== null) {
            $duplicateIndex = $this->findDeclaredWeightDuplicateIndex(
                $validEntries,
                $referenceDate,
                $startingWeight,
            );

            if ($duplicateIndex !== null) {
                array_splice($chartPoints, $duplicateIndex, 1);
            }

            array_unshift($chartPoints, [
                'date' => $referenceDate,
                'weight' => $startingWeight,
                'initial' => true,
            ]);
        }

        $latestEntry = $validEntries === [] ? null : $validEntries[array_key_last($validEntries)];
        $latestWeight = $latestEntry['weight'] ?? $startingWeight;
        $latestWeightDate = $latestEntry['date'] ?? ($startingWeight !== null ? $referenceDate : null);
        $trackingEndDate = $latestWeightDate ?? $referenceDate;
        $durationDays = $latestEntry === null ? null : (int) $referenceDate->diff($trackingEndDate)->format('%r%a');
        $totalChange = $startingWeight !== null && $latestEntry !== null
            ? $latestEntry['weight'] - $startingWeight
            : null;
        $totalChangePercentage = $totalChange !== null && $startingWeight > 0
            ? ($totalChange / $startingWeight) * 100
            : null;
        $averageWeeklyChange = $totalChange !== null && $durationDays !== null && $durationDays > 0
            ? $totalChange / ($durationDays / 7)
            : null;
        $previousMeasurementChange = count($validEntries) >= 2
            ? $validEntries[array_key_last($validEntries)]['weight'] - $validEntries[array_key_last($validEntries) - 1]['weight']
            : null;
        $thirtyDayChange = $latestEntry === null
            ? null
            : $this->calculateThirtyDayChange($chartPoints, $latestEntry);

        $allTimeTrend = $this->linearRegression($validEntries);
        $fourWeekTrend = $this->linearRegression($this->pointsWithinLatestPeriod(
            $validEntries,
            self::FOUR_WEEK_PERIOD_DAYS,
        ));
        $eightWeekTrend = $this->linearRegression($this->pointsWithinLatestPeriod(
            $validEntries,
            self::EIGHT_WEEK_PERIOD_DAYS,
        ));

        return new MedicalWeightReportData(
            generatedAt: $generatedAt,
            trackingStartDate: $referenceDate,
            trackingEndDate: $trackingEndDate,
            age: $profile->getBirthDate()->diff($generatedAt)->y,
            height: $profile->getHeight(),
            startingWeight: $startingWeight,
            startingBmi: $this->calculateBmi($startingWeight, $profile->getHeight()),
            latestWeight: $latestWeight,
            currentBmi: $this->calculateBmi($latestWeight, $profile->getHeight()),
            latestWeightDate: $latestWeightDate,
            latestWeightIsDeclared: $latestEntry === null && $startingWeight !== null,
            totalChange: $totalChange,
            totalChangePercentage: $totalChangePercentage,
            averageWeeklyChange: $averageWeeklyChange,
            trackingDurationDays: $durationDays,
            previousMeasurementChange: $previousMeasurementChange,
            thirtyDayChange: $thirtyDayChange,
            measurementCount: count($validEntries),
            invalidMeasurementCount: $invalidMeasurementCount,
            chartDataUri: $this->buildChartDataUri(
                $chartPoints,
                $fourWeekTrend,
                $eightWeekTrend,
                $allTimeTrend,
            ),
            hasFourWeekTrend: $fourWeekTrend !== null,
            hasEightWeekTrend: $eightWeekTrend !== null,
            hasAllTimeTrend: $allTimeTrend !== null,
            foodEntryCount: $foodReport->entryCount,
            averageHungerLevel: $foodReport->averageHungerLevel,
            averagePleasureLevel: $foodReport->averagePleasureLevel,
            habitSummary: $this->normalizeOptionalText($habitSummary),
            medicalNotes: $this->normalizeOptionalText($medicalNotes),
        );
    }

    private function validWeight(?float $weight): ?float
    {
        return $weight !== null && is_finite($weight) && $weight > 0 ? $weight : null;
    }

    private function calculateBmi(?float $weight, float $heightInCentimeters): ?float
    {
        if ($weight === null || $heightInCentimeters <= 0) {
            return null;
        }

        $heightInMeters = $heightInCentimeters / 100;

        return $weight / ($heightInMeters ** 2);
    }

    /**
     * @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points
     * @return list<array{date: \DateTimeImmutable, weight: float, initial: bool}>
     */
    private function pointsWithinLatestPeriod(array $points, int $periodInDays): array
    {
        if ($points === []) {
            return [];
        }

        $latestDate = $points[array_key_last($points)]['date'];
        $cutoffDate = $latestDate->modify(sprintf('-%d days', $periodInDays));

        return array_values(array_filter(
            $points,
            static fn (array $point): bool => $point['date'] >= $cutoffDate,
        ));
    }

    /** @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $entries */
    private function findDeclaredWeightDuplicateIndex(
        array $entries,
        \DateTimeImmutable $referenceDate,
        float $startingWeight,
    ): ?int
    {
        foreach ($entries as $index => $entry) {
            if ($entry['date']->format('Y-m-d') === $referenceDate->format('Y-m-d')
                && abs($entry['weight'] - $startingWeight) < 0.05
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points
     * @param array{date: \DateTimeImmutable, weight: float, initial: bool} $latestEntry
     */
    private function calculateThirtyDayChange(array $points, array $latestEntry): ?float
    {
        $cutoff = $latestEntry['date']->modify('-'.self::RECENT_PERIOD_DAYS.' days');
        $referencePoint = null;
        foreach ($points as $point) {
            if ($point['date'] <= $cutoff) {
                $referencePoint = $point;
            }
        }

        if ($referencePoint === null) {
            return null;
        }

        $distanceFromCutoff = abs((int) $referencePoint['date']->diff($cutoff)->format('%r%a'));
        if ($distanceFromCutoff > self::THIRTY_DAY_REFERENCE_TOLERANCE_DAYS) {
            return null;
        }

        return $latestEntry['weight'] - $referencePoint['weight'];
    }

    /**
     * @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points
     * @return array{slope: float, intercept: float, origin: int, start: int, end: int}|null
     */
    private function linearRegression(array $points): ?array
    {
        if (!$this->hasAtLeastDistinctDates($points, 2)) {
            return null;
        }

        $origin = $points[0]['date']->getTimestamp();
        $xValues = array_map(
            static fn (array $point): float => ($point['date']->getTimestamp() - $origin) / 86400,
            $points,
        );
        $meanX = array_sum($xValues) / count($xValues);
        $meanY = array_sum(array_column($points, 'weight')) / count($points);
        $covariance = 0.0;
        $variance = 0.0;

        foreach ($points as $index => $point) {
            $xDifference = $xValues[$index] - $meanX;
            $covariance += $xDifference * ($point['weight'] - $meanY);
            $variance += $xDifference ** 2;
        }

        if ($variance <= 0) {
            return null;
        }

        $slope = $covariance / $variance;

        return [
            'slope' => $slope,
            'intercept' => $meanY - ($slope * $meanX),
            'origin' => $origin,
            'start' => $points[0]['date']->getTimestamp(),
            'end' => $points[array_key_last($points)]['date']->getTimestamp(),
        ];
    }

    /** @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points */
    private function hasAtLeastDistinctDates(array $points, int $minimum): bool
    {
        return count(array_unique(array_map(
            static fn (array $point): string => $point['date']->format('Y-m-d'),
            $points,
        ))) >= $minimum;
    }

    /**
     * @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points
     * @param array{slope: float, intercept: float, origin: int, start: int, end: int}|null $fourWeekTrend
     * @param array{slope: float, intercept: float, origin: int, start: int, end: int}|null $eightWeekTrend
     * @param array{slope: float, intercept: float, origin: int, start: int, end: int}|null $allTimeTrend
     */
    private function buildChartDataUri(
        array $points,
        ?array $fourWeekTrend,
        ?array $eightWeekTrend,
        ?array $allTimeTrend,
    ): string
    {
        $width = 760;
        $height = 300;
        $left = 54;
        $right = 20;
        $top = 30;
        $bottom = 42;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;

        if ($points === []) {
            $svg = sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="#fff"/><text x="380" y="150" text-anchor="middle" fill="#68756f" font-family="DejaVu Sans" font-size="15">Aucune donnée pondérale disponible</text></svg>',
                $width,
                $height,
                $width,
                $height,
            );

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        }

        $points = $this->placeDeclaredWeightAtChartStart($points);
        $timestamps = array_map(static fn (array $point): int => $point['date']->getTimestamp(), $points);
        $weights = array_column($points, 'weight');
        $minimumTimestamp = min($timestamps);
        $maximumTimestamp = max($timestamps);
        $timestampRange = max(1, $maximumTimestamp - $minimumTimestamp);
        $weightRange = max($weights) - min($weights);
        $weightMargin = max(1.0, $weightRange * 0.12);
        $minimumWeight = floor((min($weights) - $weightMargin) * 2) / 2;
        $maximumWeight = ceil((max($weights) + $weightMargin) * 2) / 2;
        $displayedWeightRange = max(1.0, $maximumWeight - $minimumWeight);
        $x = static fn (int $timestamp): float => $left + (($timestamp - $minimumTimestamp) / $timestampRange) * $plotWidth;
        $y = static fn (float $weight): float => $top + (($maximumWeight - $weight) / $displayedWeightRange) * $plotHeight;
        $svgParts = [sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">', $width, $height, $width, $height)];
        $svgParts[] = '<rect width="100%" height="100%" rx="8" fill="#ffffff"/>';

        for ($index = 0; $index <= 4; $index++) {
            $gridY = $top + ($plotHeight / 4) * $index;
            $labelWeight = $maximumWeight - ($displayedWeightRange / 4) * $index;
            $svgParts[] = sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#dce4e0" stroke-width="1"/>', $left, $gridY, $width - $right, $gridY);
            $svgParts[] = sprintf('<text x="%d" y="%.1f" text-anchor="end" fill="#53645d" font-family="DejaVu Sans" font-size="10">%s kg</text>', $left - 7, $gridY + 3, number_format($labelWeight, 1, ',', ''));
        }

        for ($index = 0; $index <= 4; $index++) {
            $tickTimestamp = (int) round($minimumTimestamp + ($timestampRange / 4) * $index);
            $tickX = $x($tickTimestamp);
            $svgParts[] = sprintf('<text x="%.1f" y="%d" text-anchor="middle" fill="#53645d" font-family="DejaVu Sans" font-size="9">%s</text>', $tickX, $height - 13, date('d/m/y', $tickTimestamp));
        }

        $polylinePoints = array_map(
            static fn (array $point): string => sprintf('%.1f,%.1f', $x($point['date']->getTimestamp()), $y($point['weight'])),
            $points,
        );
        if (count($points) > 1) {
            $svgParts[] = sprintf('<polyline points="%s" fill="none" stroke="#17664f" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>', implode(' ', $polylinePoints));
        }

        $this->appendTrendLine($svgParts, $allTimeTrend, $x, $y, '#526a99', '7 5');
        $this->appendTrendLine($svgParts, $eightWeekTrend, $x, $y, '#7a5a91', '10 3 2 3');
        $this->appendTrendLine($svgParts, $fourWeekTrend, $x, $y, '#b36a23', '3 4');

        foreach ($points as $point) {
            $pointX = $x($point['date']->getTimestamp());
            $pointY = $y($point['weight']);
            if ($point['initial']) {
                $svgParts[] = sprintf('<rect x="%.1f" y="%.1f" width="10" height="10" transform="rotate(45 %.1f %.1f)" fill="#ffffff" stroke="#17664f" stroke-width="2"/>', $pointX - 5, $pointY - 5, $pointX, $pointY);
            } else {
                $svgParts[] = sprintf('<circle cx="%.1f" cy="%.1f" r="3.2" fill="#ffffff" stroke="#17664f" stroke-width="2"/>', $pointX, $pointY);
            }
        }

        $firstPoint = $points[0];
        $lastPoint = $points[array_key_last($points)];
        $svgParts[] = $this->pointLabel($firstPoint, $x($firstPoint['date']->getTimestamp()), $y($firstPoint['weight']), 'start');
        if (count($points) > 1) {
            $svgParts[] = $this->pointLabel($lastPoint, $x($lastPoint['date']->getTimestamp()), $y($lastPoint['weight']), 'end');
        }

        $legendX = $left;
        $svgParts[] = sprintf('<line x1="%d" y1="14" x2="%d" y2="14" stroke="#17664f" stroke-width="2.5"/><text x="%d" y="17" fill="#34423d" font-family="DejaVu Sans" font-size="9">Poids réel</text>', $legendX, $legendX + 22, $legendX + 27);
        if ($fourWeekTrend !== null) {
            $svgParts[] = sprintf('<line x1="%d" y1="14" x2="%d" y2="14" stroke="#b36a23" stroke-width="2" stroke-dasharray="3 4"/><text x="%d" y="17" fill="#34423d" font-family="DejaVu Sans" font-size="9">Tendance 4 semaines</text>', $legendX + 92, $legendX + 114, $legendX + 119);
        }
        if ($eightWeekTrend !== null) {
            $svgParts[] = sprintf('<line x1="%d" y1="14" x2="%d" y2="14" stroke="#7a5a91" stroke-width="2" stroke-dasharray="10 3 2 3"/><text x="%d" y="17" fill="#34423d" font-family="DejaVu Sans" font-size="9">Tendance 8 semaines</text>', $legendX + 242, $legendX + 264, $legendX + 269);
        }
        if ($allTimeTrend !== null) {
            $svgParts[] = sprintf('<line x1="%d" y1="14" x2="%d" y2="14" stroke="#526a99" stroke-width="2" stroke-dasharray="7 5"/><text x="%d" y="17" fill="#34423d" font-family="DejaVu Sans" font-size="9">Depuis le début</text>', $legendX + 394, $legendX + 416, $legendX + 421);
        }

        $svgParts[] = '</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode(implode('', $svgParts));
    }

    /**
     * The declared starting weight is a profile reference, not a dated follow-up measurement.
     * When imported measurements predate the profile creation date, it gets a small visual slot
     * before the first real measurement without changing the dates used by any calculation.
     *
     * @param list<array{date: \DateTimeImmutable, weight: float, initial: bool}> $points
     * @return list<array{date: \DateTimeImmutable, weight: float, initial: bool}>
     */
    private function placeDeclaredWeightAtChartStart(array $points): array
    {
        if (count($points) < 2 || !$points[0]['initial'] || $points[0]['date'] < $points[1]['date']) {
            return $points;
        }

        $firstMeasurementTimestamp = $points[1]['date']->getTimestamp();
        $lastMeasurementTimestamp = $points[array_key_last($points)]['date']->getTimestamp();
        $measurementRange = max(86400, $lastMeasurementTimestamp - $firstMeasurementTimestamp);
        $visualOffset = min(7 * 86400, max(86400, (int) round($measurementRange * 0.03)));
        $points[0]['date'] = $points[1]['date']->modify(sprintf('-%d seconds', $visualOffset));

        return $points;
    }

    /**
     * @param list<string> $svgParts
     * @param array{slope: float, intercept: float, origin: int, start: int, end: int}|null $trend
     * @param \Closure(int): float $x
     * @param \Closure(float): float $y
     */
    private function appendTrendLine(array &$svgParts, ?array $trend, \Closure $x, \Closure $y, string $color, string $dash): void
    {
        if ($trend === null) {
            return;
        }

        $startDays = ($trend['start'] - $trend['origin']) / 86400;
        $endDays = ($trend['end'] - $trend['origin']) / 86400;
        $svgParts[] = sprintf(
            '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2" stroke-dasharray="%s"/>',
            $x($trend['start']),
            $y($trend['intercept'] + ($trend['slope'] * $startDays)),
            $x($trend['end']),
            $y($trend['intercept'] + ($trend['slope'] * $endDays)),
            $color,
            $dash,
        );
    }

    /** @param array{date: \DateTimeImmutable, weight: float, initial: bool} $point */
    private function pointLabel(array $point, float $x, float $y, string $anchor): string
    {
        $labelX = $anchor === 'start' ? $x + 7 : $x - 7;

        return sprintf(
            '<text x="%.1f" y="%.1f" text-anchor="%s" fill="#17251f" font-family="DejaVu Sans" font-size="10" font-weight="bold">%s kg</text>',
            $labelX,
            max(28, $y - 8),
            $anchor,
            number_format($point['weight'], 1, ',', ''),
        );
    }

    private function normalizeOptionalText(?string $text): ?string
    {
        $normalizedText = trim((string) $text);

        return $normalizedText === '' ? null : $normalizedText;
    }
}
