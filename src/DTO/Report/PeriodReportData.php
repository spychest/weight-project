<?php

namespace App\DTO\Report;

final readonly class PeriodReportData
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public WeightReportData $weight,
        public HydrationReportData $hydration,
        public FoodReportData $food,
        public CheckinReportData $checkin,
        public SleepReportData $sleep,
        public ActivityReportData $activity,
        public MilestoneReportData $milestone,
    ) {
    }
}