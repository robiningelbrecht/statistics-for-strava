<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Milestone\Context\GearDistanceContext;
use App\Domain\Milestone\Context\GearElevationContext;
use App\Domain\Milestone\Context\GearMovingTimeContext;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Hour;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class GearMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private UnitSystem $unitSystem,
        private MilestoneIdFactory $milestoneIdFactory,
    ) {
    }

    private const array METRIC_DISTANCE_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000, 40_000, 50_000,
    ];

    private const array IMPERIAL_DISTANCE_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000,
    ];

    private const array METRIC_ELEVATION_THRESHOLDS = [
        500, 1_000, 2_500, 5_000, 10_000, 25_000, 50_000,
        75_000, 100_000, 150_000, 200_000,
    ];

    private const array IMPERIAL_ELEVATION_THRESHOLDS = [
        1_000, 2_500, 5_000, 10_000, 25_000, 50_000, 75_000,
        100_000, 150_000, 200_000, 300_000, 500_000,
    ];

    private const array MOVING_TIME_THRESHOLDS = [
        24, 48, 100, 168, 250, 500, 750, 1_000,
        1_500, 2_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT a.startDateTime, a.gearId, a.distance, a.elevation, a.movingTimeInSeconds, g.name as gearName
             FROM Activity a
             INNER JOIN Gear g ON a.gearId = g.gearId
             WHERE a.gearId IS NOT NULL
             ORDER BY a.startDateTime ASC'
        )->fetchAllAssociative();

        $isImperial = UnitSystem::IMPERIAL === $this->unitSystem;
        $distanceThresholds = $isImperial ? self::IMPERIAL_DISTANCE_THRESHOLDS : self::METRIC_DISTANCE_THRESHOLDS;
        $elevationThresholds = $isImperial ? self::IMPERIAL_ELEVATION_THRESHOLDS : self::METRIC_ELEVATION_THRESHOLDS;
        $distanceSymbol = $this->unitSystem->distanceSymbol();
        $elevationSymbol = $this->unitSystem->elevationSymbol();

        $milestones = [];

        /** @var array<string, array{distanceM: float, elevationM: float, seconds: int, name: string, distIdx: int, elevIdx: int, timeIdx: int, distPrev: ?Milestone, elevPrev: ?Milestone, timePrev: ?Milestone}> $gearState */
        $gearState = [];

        foreach ($rows as $row) {
            $gearId = $row['gearId'];
            $gearName = $row['gearName'];
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

            if (!isset($gearState[$gearId])) {
                $gearState[$gearId] = [
                    'distanceM' => 0.0, 'elevationM' => 0.0, 'seconds' => 0,
                    'name' => $gearName,
                    'distIdx' => 0, 'elevIdx' => 0, 'timeIdx' => 0,
                    'distPrev' => null, 'elevPrev' => null, 'timePrev' => null,
                ];
            }

            $distanceM = (float) $row['distance'];
            $elevationM = (float) $row['elevation'];
            $seconds = (int) $row['movingTimeInSeconds'];

            $state = &$gearState[$gearId];

            if ($distanceM > 0) {
                $state['distanceM'] += $distanceM;
                $cumulativeInUnit = Meter::from($state['distanceM'])->toKilometer()->toUnitSystem($this->unitSystem);

                while ($state['distIdx'] < count($distanceThresholds) && $cumulativeInUnit->toFloat() >= $distanceThresholds[$state['distIdx']]) {
                    $threshold = $distanceThresholds[$state['distIdx']];
                    $thresholdInUnit = $this->unitSystem->distance($threshold);

                    $milestone = Milestone::create(
                        id: $this->milestoneIdFactory->create(),
                        achievedOn: $achievedOn,
                        category: MilestoneCategory::GEAR_DISTANCE,
                        sportType: null,
                        activityId: null,
                        title: number_format($threshold).' '.$distanceSymbol.' on '.$gearName,
                        context: new GearDistanceContext(
                            gearName: $gearName,
                            threshold: $thresholdInUnit,
                        ),
                        previous: $this->buildDistancePreviousMilestone($state['distPrev'], $distanceSymbol),
                    );

                    $milestones[] = $milestone;
                    $state['distPrev'] = $milestone;
                    ++$state['distIdx'];
                }
            }

            if ($elevationM > 0) {
                $state['elevationM'] += $elevationM;
                $cumulativeInUnit = Meter::from($state['elevationM'])->toUnitSystem($this->unitSystem);

                while ($state['elevIdx'] < count($elevationThresholds) && $cumulativeInUnit->toFloat() >= $elevationThresholds[$state['elevIdx']]) {
                    $threshold = $elevationThresholds[$state['elevIdx']];
                    $thresholdInUnit = $this->unitSystem->elevation($threshold);

                    $milestone = Milestone::create(
                        id: $this->milestoneIdFactory->create(),
                        achievedOn: $achievedOn,
                        category: MilestoneCategory::GEAR_ELEVATION,
                        sportType: null,
                        activityId: null,
                        title: number_format($threshold).' '.$elevationSymbol.' on '.$gearName,
                        context: new GearElevationContext(
                            gearName: $gearName,
                            threshold: $thresholdInUnit,
                        ),
                        previous: $this->buildElevationPreviousMilestone($state['elevPrev'], $elevationSymbol),
                    );

                    $milestones[] = $milestone;
                    $state['elevPrev'] = $milestone;
                    ++$state['elevIdx'];
                }
            }

            if ($seconds > 0) {
                $state['seconds'] += $seconds;
                $cumulativeHours = $state['seconds'] / 3600;

                while ($state['timeIdx'] < count(self::MOVING_TIME_THRESHOLDS) && $cumulativeHours >= self::MOVING_TIME_THRESHOLDS[$state['timeIdx']]) {
                    $threshold = self::MOVING_TIME_THRESHOLDS[$state['timeIdx']];

                    $milestone = Milestone::create(
                        id: $this->milestoneIdFactory->create(),
                        achievedOn: $achievedOn,
                        category: MilestoneCategory::GEAR_MOVING_TIME,
                        sportType: null,
                        activityId: null,
                        title: number_format($threshold).' hours on '.$gearName,
                        context: new GearMovingTimeContext(
                            gearName: $gearName,
                            threshold: Hour::from($threshold),
                        ),
                        previous: $this->buildMovingTimePreviousMilestone($state['timePrev']),
                    );

                    $milestones[] = $milestone;
                    $state['timePrev'] = $milestone;
                    ++$state['timeIdx'];
                }
            }
        }

        return Milestones::fromArray($milestones);
    }

    private function buildDistancePreviousMilestone(?Milestone $previous, string $symbol): ?PreviousMilestone
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

    private function buildElevationPreviousMilestone(?Milestone $previous, string $symbol): ?PreviousMilestone
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

    private function buildMovingTimePreviousMilestone(?Milestone $previous): ?PreviousMilestone
    {
        if (!$previous instanceof Milestone) {
            return null;
        }

        $context = $previous->getContext();
        assert($context instanceof GearMovingTimeContext);

        return PreviousMilestone::create(
            milestoneId: $previous->getId(),
            label: number_format((int) $context->getThreshold()->toFloat()).' h',
            achievedOn: $previous->getAchievedOn(),
        );
    }
}
