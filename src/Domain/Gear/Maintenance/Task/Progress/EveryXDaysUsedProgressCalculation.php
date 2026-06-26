<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Gear\Maintenance\GearMaintenanceCountersResetMode;
use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Infrastructure\Config\Config;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EveryXDaysUsedProgressCalculation implements MaintenanceTaskProgressCalculation
{
    public function __construct(
        private Connection $connection,
        private Config $config,
        private TranslatorInterface $translator,
    ) {
    }

    public function supports(IntervalUnit $intervalUnit): bool
    {
        return IntervalUnit::EVERY_X_DAYS_USED === $intervalUnit;
    }

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $gearMaintenanceConfig = $this->config->loadGearMaintenance();
        $operator = GearMaintenanceCountersResetMode::NEXT_ACTIVITY_ONWARDS === $gearMaintenanceConfig->getResetMode() ? '>' : '>=';
        $query = '
                SELECT strftime("%Y-%m-%d", startDateTime) AS day
                FROM Activity
                WHERE gearId IN(:gearIds)
                AND startDateTime '.$operator.' (
                  SELECT startDateTime
                  FROM Activity
                  WHERE activityId = :activityId
                )
                GROUP BY day';

        $daysUsedSinceLastTagged = count($this->connection->fetchFirstColumn($query, [
            'gearIds' => $context->getGearIds()->toArray(),
            'activityId' => $context->getLastTaggedOnActivityId(),
        ], [
            'gearIds' => ArrayParameterType::STRING,
        ]));

        return MaintenanceTaskProgress::from(
            percentage: min((int) round(($daysUsedSinceLastTagged / $context->getIntervalValue()) * 100), 100),
            description: $this->translator->trans('{daysSinceLastTagged} days', [
                '{daysSinceLastTagged}' => $daysUsedSinceLastTagged,
            ]),
        );
    }
}
