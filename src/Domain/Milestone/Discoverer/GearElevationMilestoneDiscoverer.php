<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Milestone\Context\GearElevationContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class GearElevationMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private UnitSystem $unitSystem,
        private MilestoneIdFactory $milestoneIdFactory,
    ) {
    }

    private const array METRIC_THRESHOLDS = [
        500, 1_000, 2_500, 5_000, 10_000, 25_000, 50_000,
        75_000, 100_000, 150_000, 200_000,
    ];

    private const array IMPERIAL_THRESHOLDS = [
        1_000, 2_500, 5_000, 10_000, 25_000, 50_000, 75_000,
        100_000, 150_000, 200_000, 300_000, 500_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT a.startDateTime, a.gearId, a.elevation, g.name as gearName
             FROM Activity a
             INNER JOIN Gear g ON a.gearId = g.gearId
             WHERE a.gearId IS NOT NULL
             ORDER BY a.startDateTime ASC'
        )->fetchAllAssociative();

        $thresholds = UnitSystem::IMPERIAL === $this->unitSystem ? self::IMPERIAL_THRESHOLDS : self::METRIC_THRESHOLDS;
        $symbol = $this->unitSystem->elevationSymbol();

        $milestones = [];

        /** @var array<string, array{elevationM: float, name: string, idx: int, prev: ?Milestone}> $gearState */
        $gearState = [];

        foreach ($rows as $row) {
            $elevationM = (float) $row['elevation'];
            if ($elevationM <= 0) {
                continue;
            }

            $gearId = $row['gearId'];
            $gearName = $row['gearName'];
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

            if (!isset($gearState[$gearId])) {
                $gearState[$gearId] = [
                    'elevationM' => 0.0,
                    'name' => $gearName,
                    'idx' => 0,
                    'prev' => null,
                ];
            }

            $state = &$gearState[$gearId];
            $state['elevationM'] += $elevationM;
            $cumulativeInUnit = Meter::from($state['elevationM'])->toUnitSystem($this->unitSystem);

            while ($state['idx'] < count($thresholds) && $cumulativeInUnit->toFloat() >= $thresholds[$state['idx']]) {
                $threshold = $thresholds[$state['idx']];
                $thresholdInUnit = $this->unitSystem->elevation($threshold);

                $milestone = Milestone::create(
                    id: $this->milestoneIdFactory->create(),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::GEAR_ELEVATION,
                    sportType: null,
                    activityId: null,
                    context: new GearElevationContext(
                        gearName: $gearName,
                        threshold: $thresholdInUnit,
                    ),
                    previous: $this->buildPreviousMilestone($state['prev'], $symbol),
                );

                $milestones[] = $milestone;
                $state['prev'] = $milestone;
                ++$state['idx'];
            }
        }

        return Milestones::fromArray($milestones);
    }

    private function buildPreviousMilestone(?Milestone $previous, string $symbol): ?PreviousMilestone
    {
        if (!$previous instanceof Milestone) {
            return null;
        }

        $context = $previous->getContext();
        assert($context instanceof GearElevationContext);

        return PreviousMilestone::create(
            milestoneId: $previous->getId(),
            label: number_format((int) $context->getThreshold()->toFloat()).' '.$symbol,
            achievedOn: $previous->getAchievedOn(),
        );
    }
}
