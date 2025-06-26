<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge\Consistency;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;

final class ConsistencyChallengeConfiguration extends Collection
{
    public function getItemClassName(): string
    {
        return ConsistencyChallenge::class;
    }

    /**
     * @return array<int, mixed>
     */
    private static function getDefaultConfig(): array
    {
        return [
            [
                'label' => 'Ride a total of 200km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 200,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Ride a total of 600km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 600,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Ride a total of 1250km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 1250,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Complete a 100km ride',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 100,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Climb a total of 7500m',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 7500,
                'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide'],
            ],
            [
                'label' => 'Complete a 5km run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 5,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Complete a 10km run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 10,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Complete a half marathon run',
                'enabled' => true,
                'type' => 'distanceInOneActivity',
                'unit' => 'km',
                'goal' => 21.1,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Run a total of 100km',
                'enabled' => true,
                'type' => 'distance',
                'unit' => 'km',
                'goal' => 100,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
            [
                'label' => 'Climb a total of 2000m',
                'enabled' => true,
                'type' => 'elevation',
                'unit' => 'm',
                'goal' => 200,
                'sportTypesToInclude' => ['Run', 'TrailRun', 'VirtualRun'],
            ],
        ];
    }

    /**
     * @param array<int, mixed> $items
     */
    public static function fromScalarArray(array $items): self
    {
        if (empty($items)) {
            // Make sure this new feature is backwards compatible.
            // Use the old default configuration.
            $items = self::getDefaultConfig();
        }

        $consistencyChallenges = [];
        foreach ($items as $challengeConfig) {
            $sportTypesToInclude = SportTypes::empty();

            if (!is_array($challengeConfig)) {
                throw new InvalidConsistencyChallengeConfiguration('Invalid Challenge configuration provided');
            }

            foreach (['label', 'enabled', 'type', 'unit', 'goal', 'sportTypesToInclude'] as $requiredKey) {
                if (array_key_exists($requiredKey, $challengeConfig)) {
                    continue;
                }
                throw new InvalidConsistencyChallengeConfiguration(sprintf('"%s" property is required', $requiredKey));
            }

            if (empty($challengeConfig['label'])) {
                throw new InvalidConsistencyChallengeConfiguration('"label" property cannot be empty');
            }

            if (!is_bool($challengeConfig['enabled'])) {
                throw new InvalidConsistencyChallengeConfiguration('"enabled" property must be a boolean');
            }

            if (!is_array($challengeConfig['sportTypesToInclude'])) {
                throw new InvalidConsistencyChallengeConfiguration('"sportTypesToInclude" property must be an array');
            }

            if (empty($challengeConfig['sportTypesToInclude'])) {
                throw new InvalidConsistencyChallengeConfiguration('"sportTypesToInclude" property cannot be empty');
            }

            foreach ($challengeConfig['sportTypesToInclude'] as $sportTypeToInclude) {
                if (!$sportType = SportType::tryFrom($sportTypeToInclude)) {
                    throw new InvalidConsistencyChallengeConfiguration(sprintf('"%s" is not a valid sport type', $sportTypeToInclude));
                }
                $sportTypesToInclude->add($sportType);
            }

            $consistencyChallenges[] = EddingtonConfigItem::create(
                label: $eddingtonConfig['label'],
                showInNavBar: $eddingtonConfig['showInNavBar'],
                sportTypesToInclude: $sportTypesToInclude,
            );
        }

        return self::fromArray($consistencyChallenges);
    }
}
