<?php

declare(strict_types=1);

namespace App\Domain\Activity\Eddington;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\Eddington\Config\EddingtonConfiguration;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class EddingtonCalculator
{
    public function __construct(
        private ActivityIdRepository $activityIdRepository,
        private ActivitiesEnricher $activitiesEnricher,
        private EddingtonConfiguration $eddingtonConfiguration,
        private UnitSystem $unitSystem,
    ) {
    }

    /**
     * @return array<string, Eddington>
     */
    public function calculate(): array
    {
        $eddingtons = [];
        /** @var Config\EddingtonConfigItem $eddingtonConfigItem */
        foreach ($this->eddingtonConfiguration as $eddingtonConfigItem) {
            $activityIds = $this->activityIdRepository->findBySportTypes($eddingtonConfigItem->getSportTypesToInclude());
            if ($activityIds->isEmpty()) {
                continue;
            }

            $eddington = Eddington::getInstance(
                activities: $this->activitiesEnricher->getEnrichedActivitiesByActivityIds($activityIds),
                config: $eddingtonConfigItem,
                unitSystem: $this->unitSystem
            );
            if ($eddington->getNumber() <= 0) {
                continue;
            }
            $eddingtons[$eddingtonConfigItem->getId()] = $eddington;
        }

        return $eddingtons;
    }
}
