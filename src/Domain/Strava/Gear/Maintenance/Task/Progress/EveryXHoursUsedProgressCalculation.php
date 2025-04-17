<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Gear\Maintenance\Task\IntervalUnit;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EveryXHoursUsedProgressCalculation implements MaintenanceTaskProgressCalculation
{
    public function __construct(
        private Connection $connection,
        private TranslatorInterface $translator,
    ) {
    }

    public function supports(IntervalUnit $intervalUnit): bool
    {
        return IntervalUnit::EVERY_X_HOURS_USED === $intervalUnit;
    }

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $query = '
                SELECT SUM(movingTimeInSeconds) AS movingTimeInSeconds
                FROM Activity
                WHERE gearId = :gearId
                AND startDateTime > (
                  SELECT startDateTime
                  FROM Activity
                  WHERE activityId = :activityId
              )';

        $movingTimeInSecondsSinceLastTagged = $this->connection->fetchOne($query, [
            'gearId' => $context->getGearId(),
            'activityId' => $context->getLastTaggedOnActivityId(),
        ]);
        $movingTimeInHoursSinceLastTagged = $movingTimeInSecondsSinceLastTagged / 3600;

        return MaintenanceTaskProgress::from(
            percentage: min((int) round(($movingTimeInHoursSinceLastTagged / $context->getIntervalValue()) * 100), 100),
            description: $this->translator->trans('{hoursSinceLastTagged} hours', [
                '{hoursSinceLastTagged}' => round($movingTimeInHoursSinceLastTagged),
            ]),
        );
    }
}
