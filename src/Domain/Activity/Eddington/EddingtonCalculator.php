<?php

declare(strict_types=1);

namespace App\Domain\Activity\Eddington;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\Eddington\Config\EddingtonConfiguration;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class EddingtonCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
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
            $activities = $this->activityRepository->findBySportTypes($eddingtonConfigItem->getSportTypesToInclude());
            if ($activities->isEmpty()) {
                continue;
            }

            $eddington = Eddington::getInstance(
                activities: $activities,
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
