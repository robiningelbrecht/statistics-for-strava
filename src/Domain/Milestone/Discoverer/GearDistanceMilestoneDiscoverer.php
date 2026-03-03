<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Milestone\Context\GearDistanceContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class GearDistanceMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private UnitSystem $unitSystem,
        private MilestoneIdFactory $milestoneIdFactory,
    ) {
    }

    private const array METRIC_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000, 40_000, 50_000,
    ];

    private const array IMPERIAL_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT a.startDateTime, a.gearId, a.distance, g.name as gearName
             FROM Activity a
             INNER JOIN Gear g ON a.gearId = g.gearId
             WHERE a.gearId IS NOT NULL
             ORDER BY a.startDateTime ASC'
        )->fetchAllAssociative();

        $thresholds = UnitSystem::IMPERIAL === $this->unitSystem ? self::IMPERIAL_THRESHOLDS : self::METRIC_THRESHOLDS;
        $symbol = $this->unitSystem->distanceSymbol();

        $milestones = [];

        /** @var array<string, array{distanceM: float, name: string, idx: int, prev: ?Milestone}> $gearState */
        $gearState = [];

        foreach ($rows as $row) {
            $distanceM = (float) $row['distance'];
            if ($distanceM <= 0) {
                continue;
            }

            $gearId = $row['gearId'];
            $gearName = $row['gearName'];
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

            if (!isset($gearState[$gearId])) {
                $gearState[$gearId] = [
                    'distanceM' => 0.0,
                    'name' => $gearName,
                    'idx' => 0,
                    'prev' => null,
                ];
            }

            $state = &$gearState[$gearId];
            $state['distanceM'] += $distanceM;
            $cumulativeInUnit = Meter::from($state['distanceM'])->toKilometer()->toUnitSystem($this->unitSystem);

            while ($state['idx'] < count($thresholds) && $cumulativeInUnit->toFloat() >= $thresholds[$state['idx']]) {
                $threshold = $thresholds[$state['idx']];
                $thresholdInUnit = $this->unitSystem->distance($threshold);

                $milestone = Milestone::create(
                    id: $this->milestoneIdFactory->create(),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::GEAR_DISTANCE,
                    sportType: null,
                    activityId: null,
                    context: new GearDistanceContext(
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
        assert($context instanceof GearDistanceContext);

        return PreviousMilestone::create(
            milestoneId: $previous->getId(),
            label: number_format((int) $context->getThreshold()->toFloat()).' '.$symbol,
            achievedOn: $previous->getAchievedOn(),
        );
    }
}
