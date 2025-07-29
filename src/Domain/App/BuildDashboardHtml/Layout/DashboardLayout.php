<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Layout;

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

    /**
     * @return list<array{widget: string, width: int, enabled: bool}>
     */
    private static function default(): array
    {
        return [
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
        ];
    }

    /**
     * @param array<int, mixed> $config|null
     */
    public static function fromArray(
        ?array $config,
    ): self {
        if (empty($config)) {
            $config = self::default();
        }

        foreach ($config as $widgetConfig) {
            foreach (['widget', 'width', 'enabled'] as $requiredKey) {
                if (array_key_exists($requiredKey, $widgetConfig)) {
                    continue;
                }
                throw new InvalidDashboardLayout(sprintf('"%s" property is required for each custom gear', $requiredKey));
            }

            if (!is_bool($widgetConfig['enabled'])) {
                throw new InvalidDashboardLayout('"enabled" property must be a boolean');
            }

            if (!is_int($widgetConfig['width'])) {
                throw new InvalidDashboardLayout('"width" property must be a valid integer');
            }

            if (!in_array($widgetConfig['width'], [33, 50, 66, 100])) {
                throw new InvalidDashboardLayout(sprintf('"width" property must be one of [33, 50, 66, 100], found %s', $widgetConfig['width']));
            }
        }

        return new self($config); // @phpstan-ignore argument.type
    }
}
