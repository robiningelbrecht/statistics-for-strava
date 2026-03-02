<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeElevationContext;
use App\Domain\Milestone\FunComparison\ElevationFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneId;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class CumulativeElevationMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private Connection $connection,
        private UnitSystem $unitSystem,
    ) {
    }

    private const array METRIC_THRESHOLDS = [
        500, 1_000, 2_500, 5_000, 8_849, 10_000, 17_772, 25_000,
        50_000, 75_000, 100_000, 150_000, 200_000, 300_000,
        400_000, 500_000, 750_000, 1_000_000,
    ];

    private const array IMPERIAL_THRESHOLDS = [
        1_000, 2_500, 5_000, 10_000, 25_000, 50_000, 75_000,
        100_000, 150_000, 200_000, 300_000, 500_000, 750_000,
        1_000_000, 1_500_000, 2_000_000, 2_500_000, 3_000_000,
    ];

    public function discover(): Milestones
    {
        $rows = $this->connection->executeQuery(
            'SELECT startDateTime, sportType, elevation
             FROM Activity
             ORDER BY startDateTime ASC'
        )->fetchAllAssociative();

        $isImperial = UnitSystem::IMPERIAL === $this->unitSystem;
        $thresholds = $isImperial ? self::IMPERIAL_THRESHOLDS : self::METRIC_THRESHOLDS;
        $symbol = $this->unitSystem->elevationSymbol();

        $milestones = [];
        $cumulativeElevationM = 0.0;
        $thresholdIndex = 0;
        /** @var ?Milestone $previousMilestone */
        $previousMilestone = null;

        foreach ($rows as $row) {
            $elevationM = (float) $row['elevation'];
            if ($elevationM <= 0) {
                continue;
            }

            $cumulativeElevationM += $elevationM;
            $cumulativeInUnit = Meter::from($cumulativeElevationM)->toUnitSystem($this->unitSystem);

            while ($thresholdIndex < count($thresholds) && $cumulativeInUnit->toFloat() >= $thresholds[$thresholdIndex]) {
                $threshold = $thresholds[$thresholdIndex];
                $thresholdInUnit = $this->unitSystem->elevation($threshold);
                $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

                $previous = null;
                if ($previousMilestone) {
                    $previousContext = $previousMilestone->getContext();
                    assert($previousContext instanceof CumulativeElevationContext);
                    $previous = PreviousMilestone::create(
                        milestoneId: $previousMilestone->getId(),
                        label: number_format((int) $previousContext->getThreshold()->toFloat()).' '.$symbol,
                        achievedOn: $previousMilestone->getAchievedOn(),
                    );
                }

                $milestone = Milestone::create(
                    id: MilestoneId::random(),
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::CUMULATIVE_ELEVATION,
                    sportType: SportType::tryFrom($row['sportType']),
                    activityId: null,
                    title: number_format($threshold).' '.$symbol,
                    context: new CumulativeElevationContext(
                        threshold: $thresholdInUnit,
                        totalElevation: $cumulativeInUnit,
                    ),
                    previous: $previous,
                    funComparison: ElevationFunComparison::resolve($thresholdInUnit->toMeter()),
                );

                $milestones[] = $milestone;
                $previousMilestone = $milestone;
                ++$thresholdIndex;
            }
        }

        return Milestones::fromArray($milestones);
    }
}
