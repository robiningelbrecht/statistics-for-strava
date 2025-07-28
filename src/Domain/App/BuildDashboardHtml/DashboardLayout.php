<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

final readonly class DashboardLayout implements \IteratorAggregate
{
    private function __construct(
        /** @var list<array{widget: string, width: int, enabled: bool}> */
        private array $config,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->config);
    }

    public static function default(): self
    {
        return new self([
            ['widget' => 'mostRecentActivities', 'width' => 66, 'enabled' => true],
            ['widget' => 'introText', 'width' => 33, 'enabled' => true],
            ['widget' => 'weeklyStats', 'width' => 100, 'enabled' => true],
            ['widget' => 'peakPowerOutputs', 'width' => 50, 'enabled' => true],
            ['widget' => 'heartRateZones', 'width' => 50, 'enabled' => true],
            ['widget' => 'activityIntensity', 'width' => 100, 'enabled' => true],
            ['widget' => 'trainingLoad', 'width' => 100, 'enabled' => true],
            ['widget' => 'weekdayStats', 'width' => 50, 'enabled' => true],
            ['widget' => 'dayTimeStats', 'width' => 50, 'enabled' => true],
            ['widget' => 'distanceBreakdown', 'width' => 100, 'enabled' => true],
            ['widget' => 'bestEfforts', 'width' => 100, 'enabled' => true],
            ['widget' => 'yearlyDistances', 'width' => 100, 'enabled' => true],
            ['widget' => 'challengeConsistency', 'width' => 50, 'enabled' => true],
            ['widget' => 'ftpHistory', 'width' => 50, 'enabled' => true],
        ]);
    }
}
