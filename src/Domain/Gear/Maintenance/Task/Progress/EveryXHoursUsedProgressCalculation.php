<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Gear\Maintenance\GearMaintenanceCountersResetMode;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EveryXHoursUsedProgressCalculation implements MaintenanceTaskProgressCalculation
{
    public function __construct(
        private Connection $connection,
        private GearMaintenanceConfig $config,
        private TranslatorInterface $translator,
    ) {
    }

    public function supports(IntervalUnit $intervalUnit): bool
    {
        return IntervalUnit::EVERY_X_HOURS_USED === $intervalUnit;
    }

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $operator = GearMaintenanceCountersResetMode::NEXT_ACTIVITY_ONWARDS === $this->config->getResetMode() ? '>' : '>=';
        $query = '
                SELECT SUM(movingTimeInSeconds) AS movingTimeInSeconds
                FROM Activity
                WHERE gearId IN(:gearIds)
                AND startDateTime '.$operator.' (
                  SELECT startDateTime
                  FROM Activity
                  WHERE activityId = :activityId
              )';

        $movingTimeInSecondsSinceLastTagged = $this->connection->fetchOne($query, [
            'gearIds' => $context->getGearIds()->toArray(),
            'activityId' => $context->getLastTaggedOnActivityId(),
        ], [
            'gearIds' => ArrayParameterType::STRING,
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
