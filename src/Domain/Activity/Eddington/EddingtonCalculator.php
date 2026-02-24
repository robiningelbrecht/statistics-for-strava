<?php

declare(strict_types=1);

namespace App\Domain\Activity\Eddington;

use App\Domain\Activity\Eddington\Config\EddingtonConfiguration;
use App\Domain\Activity\EnrichedActivities;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class EddingtonCalculator
{
    public function __construct(
        private EnrichedActivities $enrichedActivities,
        private EddingtonConfiguration $eddingtonConfiguration,
    ) {
    }

    /**
     * @return list<Eddington>
     */
    public function calculate(UnitSystem $unitSystem): array
    {
        $eddingtons = [];
        /** @var Config\EddingtonConfigItem $eddingtonConfigItem */
        foreach ($this->eddingtonConfiguration as $eddingtonConfigItem) {
            $activities = $this->enrichedActivities->findBySportTypes($eddingtonConfigItem->getSportTypesToInclude());
            if ($activities->isEmpty()) {
                continue;
            }

            $eddington = Eddington::getInstance(
                activities: $activities,
                config: $eddingtonConfigItem,
                unitSystem: $unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            $eddingtons[] = $eddington;
        }

        return $eddingtons;
    }
}
