<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

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
            throw new InvalidHeartZoneConfiguration('"default" property must be an array');
        }

        self::sortZones($config['default']);
        self::guardValidAndSequentialZones($config['default']);

        if (array_key_exists('dateRanges', $config) && !is_array($config['dateRanges'])) {
            throw new InvalidHeartZoneConfiguration('"dateRanges" property must be an array');
        }

        foreach ($config['dateRanges'] as $date => &$zones) {
            try {
                $on = SerializableDateTime::fromString($date);
                self::sortZones($zones);
                self::guardValidAndSequentialZones($zones);
            } catch (\DateMalformedStringException) {
                throw new \InvalidArgumentException(sprintf('Invalid date "%s" set for athlete heartRateZone', $date));
            }
        }

        if (array_key_exists('sportTypes', $config) && !is_array($config['sportTypes'])) {
            throw new InvalidHeartZoneConfiguration('"sportTypes" property must be an array');
        }

        foreach ($config['sportTypes'] as $sportTypeValue => &$sportTypeConfig) {
            if (!$sportType = SportType::tryFrom($sportTypeValue)) {
                throw new InvalidHeartZoneConfiguration(sprintf('"%s" is not a valid sport type', $sportTypeValue));
            }

            if (!array_key_exists('default', $sportTypeConfig)) {
                throw new InvalidHeartZoneConfiguration(sprintf('"default" property is required for sportType %s', $sportType->value));
            }

            if (!is_array($sportTypeConfig['default'])) {
                throw new InvalidHeartZoneConfiguration(sprintf('"default" property must be an array for sportType %s', $sportType->value));
            }

            self::sortZones($sportTypeConfig['default']);
            self::guardValidAndSequentialZones($sportTypeConfig['default']);

            if (array_key_exists('dateRanges', $sportTypeConfig) && !is_array($sportTypeConfig['dateRanges'])) {
                throw new InvalidHeartZoneConfiguration(sprintf('"dateRanges" property must be an array for sportType %s', $sportType->value));
            }

            foreach ($sportTypeConfig['dateRanges'] as $date => &$zones) {
                try {
                    $on = SerializableDateTime::fromString($date);
                    self::sortZones($zones);
                    self::guardValidAndSequentialZones($zones);
                } catch (\DateMalformedStringException) {
                    throw new \InvalidArgumentException(sprintf('Invalid date "%s" set for athlete heartRateZone', $date));
                }
            }
        }

        return new self(
            mode: $mode,
            hearRateZonesConfig: []
        );
    }

    /**
     * @param array<string, array{from: int, to: int}> $zones
     */
    private static function sortZones(array &$zones): void
    {
        foreach (['zone1', 'zone2', 'zone3', 'zone4', 'zone5'] as $requiredKey) {
            if (array_key_exists($requiredKey, $zones)) {
                continue;
            }
            throw new InvalidHeartZoneConfiguration(sprintf('"%s" property is required for each range of heart zones', $requiredKey));
        }

        $zones = [
            'zone1' => $zones['zone1'],
            'zone2' => $zones['zone2'],
            'zone3' => $zones['zone3'],
            'zone4' => $zones['zone4'],
            'zone5' => $zones['zone5'],
        ];
    }

    /**
     * @param array<string, array{from: int, to: int|null}> $zones
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
                throw new InvalidHeartZoneConfiguration(sprintf("Gap detected before %s: expected 'from' to be %s, got %s", $key, $expectedFrom, $from));
            }

            // If 'to' is null, we're at the end
            if (null === $to) {
                return;
            }

            // If 'from' > 'to', it's invalid
            if ($from > $to) {
                throw new InvalidHeartZoneConfiguration(sprintf("%s has 'from' (%s) greater than 'to' (%s), which is invalid", $key, $expectedFrom, $from));
            }

            // Set next expected 'from'
            $expectedFrom = $to + 1;
        }
    }
}
