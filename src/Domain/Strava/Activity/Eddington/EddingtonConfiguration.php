<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Eddington;

use App\Domain\Strava\Activity\SportType\SportType;

final readonly class EddingtonConfiguration
{
    private function __construct(
        private array $config,
    ) {
    }

    private static function getDefaultConfig(): array
    {
        return [
            [
                'label' => 'Ride',
                'showInNavBar' => true,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Run',
                'showInNavBar' => true,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Walk',
                'showInNavBar' => false,
                'sportTypesToInclude' => ['Walk', 'Hike'],
            ],
        ];
    }

    public static function fromArray(array $config): self
    {
        if (empty($config)) {
            // Make sure this new feature is backwards compatible.
            // Return the old default configuration.
            $config = self::getDefaultConfig();
        }

        foreach ($config as $eddingtonConfig) {
            if (!is_array($eddingtonConfig)) {
                throw new InvalidEddingtonConfiguration('Invalid Eddington configuration provided');
            }

            foreach (['label', 'showInNavBar', 'sportTypesToInclude'] as $requiredKey) {
                if (array_key_exists($requiredKey, $eddingtonConfig)) {
                    continue;
                }
                throw new InvalidEddingtonConfiguration(sprintf('"%s" property is required', $requiredKey));
            }

            if (empty($eddingtonConfig['label'])) {
                throw new InvalidEddingtonConfiguration('"label" property cannot be empty');
            }

            if (!is_bool($eddingtonConfig['showInNavBar'])) {
                throw new InvalidEddingtonConfiguration('"showInNavBar" property must be a boolean');
            }

            if (!is_array($eddingtonConfig['sportTypesToInclude'])) {
                throw new InvalidEddingtonConfiguration('"sportTypesToInclude" property must be an array');
            }

            if (empty($eddingtonConfig['sportTypesToInclude'])) {
                throw new InvalidEddingtonConfiguration('"sportTypesToInclude" property cannot be empty');
            }

            foreach ($eddingtonConfig['sportTypesToInclude'] as $sportTypeToInclude) {
                if (!SportType::tryFrom($sportTypeToInclude)) {
                    throw new InvalidEddingtonConfiguration(sprintf('"%s" is not a valid sport type', $sportTypeToInclude));
                }
            }

            $uniqueActivityTypes = array_unique(array_map(
                static fn (string $sportType) => SportType::from($sportType)->getActivityType()->value,
                $eddingtonConfig['sportTypesToInclude']
            ));

            if (1 !== count($uniqueActivityTypes)) {
                throw new InvalidEddingtonConfiguration(sprintf('Eddington "%s" contains sport types with different activity types', $eddingtonConfig['label']));
            }
        }

        if (count(array_filter($config, fn (array $eddingtonConfig) => $eddingtonConfig['showInNavBar'])) > 2) {
            throw new InvalidEddingtonConfiguration('You can only have two Eddingtons with "showInNavBar" set to true.');
        }

        return new self($config);
    }
}
