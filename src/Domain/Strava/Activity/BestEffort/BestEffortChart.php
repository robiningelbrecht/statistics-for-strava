<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BestEffort;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Measurement\Unit;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BestEffortChart
{
    private function __construct(
        private ActivityType $activityType,
        private ActivityBestEfforts $bestEfforts,
        private SportTypes $sportTypes,
        private TranslatorInterface $translator,
    ) {
    }

    public static function create(
        ActivityType $activityType,
        ActivityBestEfforts $bestEfforts,
        SportTypes $sportTypes,
        TranslatorInterface $translator,
    ): self {
        return new self(
            activityType: $activityType,
            bestEfforts: $bestEfforts,
            sportTypes: $sportTypes,
            translator: $translator
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $series = [];

        $uniqueSportTypesInBestEfforts = $this->bestEfforts->getUniqueSportTypes();
        /** @var SportType $sportType */
        foreach ($this->sportTypes as $sportType) {
            if (!$uniqueSportTypesInBestEfforts->has($sportType)) {
                continue;
            }
            $series[] = [
                'name' => $sportType->trans($this->translator),
                'type' => 'bar',
                'barGap' => 0,
                'emphasis' => [
                    'focus' => 'none',
                ],
                'label' => [
                    'show' => false,
                ],
                'data' => $this->bestEfforts->getBySportType($sportType)->map(fn (ActivityBestEffort $bestEffort) => $bestEffort->getTimeInSeconds()),
            ];
        }

        return [
            'backgroundColor' => '#ffffff',
            'animation' => true,
            'color' => ['#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'],
            'grid' => [
                'top' => '30px',
                'left' => '0',
                'right' => '0',
                'bottom' => '2%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'none',
                ],
                'valueFormatter' => 'formatSeconds',
            ],
            'legend' => [
                'show' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => array_map(
                        fn (Unit $distance) => sprintf('%s%s', $distance->isLowerThanOne() ? round($distance->toFloat(), 1) : $distance->toInt(), $distance->getSymbol()),
                        $this->activityType->getDistancesForBestEffortCalculation()
                    ),
                ],
            ],
            'yAxis' => [
                'type' => 'log',
                'axisLabel' => [
                    'formatter' => 'formatSeconds',
                    'showMaxLabel' => false,
                ],
            ],
            'series' => $series,
        ];
    }
}
