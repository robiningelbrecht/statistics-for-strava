<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeDistanceContext;
use App\Domain\Milestone\FunComparison\DistanceFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class CumulativeDistanceMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private UnitSystem $unitSystem,
        private MilestoneIdFactory $milestoneIdFactory,
    ) {
    }

    private const array METRIC_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000, 40_000, 50_000, 75_000,
        100_000, 150_000, 200_000, 300_000, 400_000, 500_000,
    ];

    private const array IMPERIAL_THRESHOLDS = [
        100, 250, 500, 1_000, 2_500, 5_000, 10_000, 15_000,
        20_000, 25_000, 30_000, 40_000, 50_000, 75_000,
        100_000, 150_000, 200_000, 300_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT startDateTime, sportType, distance
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $isImperial = UnitSystem::IMPERIAL === $this->unitSystem;
        $thresholds = $isImperial ? self::IMPERIAL_THRESHOLDS : self::METRIC_THRESHOLDS;
        $symbol = $this->unitSystem->distanceSymbol();

        $milestones = [];
        $cumulativeDistanceM = 0.0;
        $thresholdIndex = 0;
        /** @var ?Milestone $previousMilestone */
        $previousMilestone = null;

        foreach ($rows as $row) {
            $distanceM = (float) $row['distance'];
            if ($distanceM <= 0) {
                continue;
            }

            $cumulativeDistanceM += $distanceM;
            $cumulativeInUnit = Meter::from($cumulativeDistanceM)->toKilometer()->toUnitSystem($this->unitSystem);

            while ($thresholdIndex < count($thresholds) && $cumulativeInUnit->toFloat() >= $thresholds[$thresholdIndex]) {
                $threshold = $thresholds[$thresholdIndex];
                $thresholdInUnit = $this->unitSystem->distance($threshold);
                $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

                $previous = null;
                if ($previousMilestone) {
                    $previousContext = $previousMilestone->getContext();
                    assert($previousContext instanceof CumulativeDistanceContext);
                    $previous = PreviousMilestone::create(
                        milestoneId: $previousMilestone->getId(),
                        label: number_format((int) $previousContext->getThreshold()->toFloat()).' '.$symbol,
                        achievedOn: $previousMilestone->getAchievedOn(),
                    );
                }

                $milestone = Milestone::create(
                    id: $this->milestoneIdFactory->create(),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::CUMULATIVE_DISTANCE,
                    sportType: SportType::tryFrom($row['sportType']),
                    activityId: null,
                    title: number_format($threshold).' '.$symbol,
                    context: new CumulativeDistanceContext(
                        threshold: $thresholdInUnit,
                        totalDistance: $cumulativeInUnit,
                    ),
                    previous: $previous,
                    funComparison: DistanceFunComparison::resolve($thresholdInUnit->toMeter()->toKilometer()),
                );

                $milestones[] = $milestone;
                $previousMilestone = $milestone;
                ++$thresholdIndex;
            }
        }

        return Milestones::fromArray($milestones);
    }
}
