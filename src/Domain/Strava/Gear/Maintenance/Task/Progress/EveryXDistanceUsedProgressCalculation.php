<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

use App\Domain\Strava\Gear\Maintenance\Task\IntervalUnit;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use Doctrine\DBAL\Connection;

final readonly class EveryXDistanceUsedProgressCalculation implements MaintenanceTaskProgressCalculation
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function supports(IntervalUnit $intervalUnit): bool
    {
        return in_array($intervalUnit, [
            IntervalUnit::EVERY_X_KILOMETERS_USED,
            IntervalUnit::EVERY_X_MILES_USED,
        ]);
    }

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $query = '
                SELECT SUM(distance) AS distance
                FROM Activity
                WHERE gearId = :gearId
                AND startDateTime > (
                  SELECT startDateTime
                  FROM Activity
                  WHERE activityId = :activityId
              )';

        $distanceSinceLastTagged = Meter::from($this->connection->fetchOne($query, [
            'gearId' => $context->getGearId(),
            'activityId' => $context->getLastTaggedOnActivityId(),
        ]) ?? 0)->toKilometer();

        if (IntervalUnit::EVERY_X_MILES_USED === $context->getIntervalUnit()) {
            $distanceSinceLastTagged = $distanceSinceLastTagged->toMiles();
        }

        return MaintenanceTaskProgress::from(
            percentage: min((int) round(($distanceSinceLastTagged->toFloat() / $context->getIntervalValue()) * 100), 100),
            description: sprintf('%s %s', $distanceSinceLastTagged->toInt(), $distanceSinceLastTagged->getSymbol()),
        );
    }
}
