<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Milestone\Context\CumulativeElevationContext;
use App\Domain\Milestone\FunComparison\ElevationFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\MilestoneIdFactory;
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
        private MilestoneIdFactory $milestoneIdFactory,
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

        $thresholds = UnitSystem::IMPERIAL === $this->unitSystem ? self::IMPERIAL_THRESHOLDS : self::METRIC_THRESHOLDS;
        $symbol = $this->unitSystem->elevationSymbol();

        $milestones = [];
        $globalElevationM = 0.0;
        $globalThresholdIndex = 0;
        /** @var ?Milestone $globalPreviousMilestone */
        $globalPreviousMilestone = null;

        /** @var array<string, float> $sportElevationsM */
        $sportElevationsM = [];
        /** @var array<string, int> $sportThresholdIndices */
        $sportThresholdIndices = [];
        /** @var array<string, ?Milestone> $sportPreviousMilestones */
        $sportPreviousMilestones = [];

        foreach ($rows as $row) {
            $elevationM = (float) $row['elevation'];
            if ($elevationM <= 0) {
                continue;
            }

            $sportType = SportType::from($row['sportType']);
            $sportTypeValue = $row['sportType'];
            $achievedOn = SerializableDateTime::fromString($row['startDateTime']);

            $globalElevationM += $elevationM;
            $globalCumulativeInUnit = Meter::from($globalElevationM)->toUnitSystem($this->unitSystem);

            while ($globalThresholdIndex < count($thresholds) && $globalCumulativeInUnit->toFloat() >= $thresholds[$globalThresholdIndex]) {
                $threshold = $thresholds[$globalThresholdIndex];
                $milestone = $this->createMilestone(
                    achievedOn: $achievedOn,
                    sportType: null,
                    threshold: $threshold,
                    previousMilestone: $globalPreviousMilestone,
                    symbol: $symbol
                );
                $milestones[] = $milestone;
                $globalPreviousMilestone = $milestone;
                ++$globalThresholdIndex;
            }

            if (!isset($sportElevationsM[$sportTypeValue])) {
                $sportElevationsM[$sportTypeValue] = 0.0;
                $sportThresholdIndices[$sportTypeValue] = 0;
                $sportPreviousMilestones[$sportTypeValue] = null;
            }
            $sportElevationsM[$sportTypeValue] += $elevationM;
            $sportCumulativeInUnit = Meter::from($sportElevationsM[$sportTypeValue])->toUnitSystem($this->unitSystem);

            while ($sportThresholdIndices[$sportTypeValue] < count($thresholds) && $sportCumulativeInUnit->toFloat() >= $thresholds[$sportThresholdIndices[$sportTypeValue]]) {
                $threshold = $thresholds[$sportThresholdIndices[$sportTypeValue]];
                $milestone = $this->createMilestone(
                    achievedOn: $achievedOn,
                    sportType: $sportType,
                    threshold: $threshold,
                    previousMilestone: $sportPreviousMilestones[$sportTypeValue],
                    symbol: $symbol
                );
                $milestones[] = $milestone;
                $sportPreviousMilestones[$sportTypeValue] = $milestone;
                ++$sportThresholdIndices[$sportTypeValue];
            }
        }

        return Milestones::fromArray($milestones);
    }

    private function createMilestone(
        SerializableDateTime $achievedOn,
        ?SportType $sportType,
        int $threshold,
        ?Milestone $previousMilestone,
        string $symbol,
    ): Milestone {
        $thresholdInUnit = $this->unitSystem->elevation($threshold);

        return Milestone::create(
            id: $this->milestoneIdFactory->create(),
            achievedOn: $achievedOn,
            category: MilestoneCategory::CUMULATIVE_ELEVATION,
            sportType: $sportType,
            activityId: null,
            title: number_format($threshold).' '.$symbol,
            context: new CumulativeElevationContext(
                threshold: $thresholdInUnit,
            ),
            previous: $this->buildPreviousMilestone($previousMilestone, $symbol),
            funComparison: ElevationFunComparison::resolve($thresholdInUnit->toMeter()),
        );
    }

    private function buildPreviousMilestone(?Milestone $previousMilestone, string $symbol): ?PreviousMilestone
    {
        if (!$previousMilestone instanceof Milestone) {
            return null;
        }

        $previousContext = $previousMilestone->getContext();
        assert($previousContext instanceof CumulativeElevationContext);

        return PreviousMilestone::create(
            milestoneId: $previousMilestone->getId(),
            label: number_format((int) $previousContext->getThreshold()->toFloat()).' '.$symbol,
            achievedOn: $previousMilestone->getAchievedOn(),
        );
    }
}
