<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Activity\Eddington\EddingtonCalculator;
use App\Domain\Milestone\Context\EddingtonContext;
use App\Domain\Milestone\FunComparison\EddingtonFunComparison;
use App\Domain\Milestone\Milestone;
use App\Domain\Milestone\MilestoneCategory;
use App\Domain\Milestone\Milestones;
use App\Domain\Milestone\PreviousMilestone;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class EddingtonMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function __construct(
        private EddingtonCalculator $eddingtonCalculator,
        private UnitSystem $unitSystem,
    ) {
    }

    private const array THRESHOLDS = [
        5, 10, 15, 20, 25, 30, 40, 50,
        60, 75, 100, 125, 150, 175, 200,
    ];

    public function discover(): Milestones
    {
        $milestones = [];

        foreach ($this->eddingtonCalculator->calculate($this->unitSystem) as $eddington) {
            $history = $eddington->getEddingtonHistory();
            /** @var ?Milestone $previousMilestone */
            $previousMilestone = null;

            foreach (self::THRESHOLDS as $threshold) {
                if (!isset($history[$threshold])) {
                    continue;
                }

                $achievedOn = $history[$threshold];

                $previous = null;
                if ($previousMilestone) {
                    $previousContext = $previousMilestone->getContext();
                    assert($previousContext instanceof EddingtonContext);
                    $previous = PreviousMilestone::create(
                        label: 'E'.$previousContext->getNumber(),
                        achievedOn: $previousMilestone->getAchievedOn(),
                    );
                }

                $milestone = Milestone::create(
                    achievedOn: $achievedOn,
                    category: MilestoneCategory::EDDINGTON,
                    sportType: null,
                    activityId: null,
                    title: $eddington->getLabel().' Eddington '.$threshold,
                    context: new EddingtonContext(
                        number: $threshold,
                    ),
                    previous: $previous,
                    funComparison: EddingtonFunComparison::resolve($threshold),
                );

                $milestones[] = $milestone;
                $previousMilestone = $milestone;
            }
        }

        return Milestones::fromArray($milestones);
    }
}
