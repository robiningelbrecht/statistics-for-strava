<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Activity\Eddington\InvalidEddingtonConfiguration;

final readonly class HeartRateZoneConfiguration
{
    private function __construct(
        private HeartRateZoneMode $mode,
        /** @var array<string, HeartRateZones> */
        private array $hearRateZonesConfig,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDefaultConfig(): array
    {
        return [
            'mode' => 'relative',
            'default' => [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 80,
                ],
                'zone5' => [
                    'from' => 91,
                    'to' => null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        if (empty($config)) {
            // Make sure this new feature is backwards compatible.
            // Use the old default configuration.
            $config = self::getDefaultConfig();
        }

        foreach (['mode', 'default'] as $requiredKey) {
            if (array_key_exists($requiredKey, $config)) {
                continue;
            }
            throw new InvalidHeartZoneConfiguration(sprintf('"%s" property is required', $requiredKey));
        }

        if (!$mode = HeartRateZoneMode::tryFrom($config['mode'])) {
            throw new InvalidHeartZoneConfiguration(sprintf('"%s" is not a valid mode', $config['mode']));
        }

        if (!is_array($config['default'])) {
            throw new InvalidEddingtonConfiguration('"default" property must be an array');
        }

        $defaultZones = self::sortZones($config['default']);
        self::guardValidAndSequentialZones($defaultZones);

        return new self(
            mode: $mode,
            hearRateZonesConfig: []
        );
    }

    /**
     * @param array<string, array{from: int, to: int}> $zones
     *
     * @return array<string, array{from: int, to: int}>
     */
    private static function sortZones(array $zones): array
    {
        foreach (['zone1', 'zone2', 'zone3', 'zone4', 'zone5'] as $requiredKey) {
            if (array_key_exists($requiredKey, $zones)) {
                continue;
            }
            throw new InvalidHeartZoneConfiguration(sprintf('"%s" property is required for each range of heart zones', $requiredKey));
        }

        return [
            'zone1' => $zones['zone1'],
            'zone2' => $zones['zone2'],
            'zone3' => $zones['zone3'],
            'zone4' => $zones['zone4'],
            'zone5' => $zones['zone5'],
        ];
    }

    /**
     * @param array<string, array{from: int, to: int}> $zones
     */
    private static function guardValidAndSequentialZones(array $zones): void
    {
        $expectedFrom = null;
        foreach ($zones as $key => $zone) {
            $from = $zone['from'];
            $to = $zone['to'];

            // First zone sets the baseline
            if (null === $expectedFrom) {
                $expectedFrom = $from;
            }

            // Validate the 'from' matches expected
            if ($from !== $expectedFrom) {
                throw new InvalidHeartZoneConfiguration(sprintf("Gap detected before %s: expected 'from' to be %s, got %s.", $key, $expectedFrom, $from));
            }

            // If 'to' is null, we're at the end
            if (null === $to) {
                return;
            }

            // If 'from' > 'to', it's invalid
            if ($from > $to) {
                throw new InvalidHeartZoneConfiguration(sprintf("%s has 'from' (%s) greater than 'to' (%s), which is invalid.", $key, $expectedFrom, $from));
            }

            // Set next expected 'from'
            $expectedFrom = $to + 1;
        }
    }
}
