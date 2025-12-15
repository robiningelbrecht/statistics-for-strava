<?php

declare(strict_types=1);

namespace App\Application\Import\ImportGear;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;

final readonly class GearImportStatus
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ImportedGearRepository $importedGearRepository,
    ) {
    }

    public function isComplete(): bool
    {
        $gearIdsOnActivities = $this->activityRepository->findUniqueGearIds(null);
        $importedGears = $this->importedGearRepository->findAll();

        foreach ($gearIdsOnActivities as $gearId) {
            if (!$importedGears->getByGearId($gearId)) {
                return false;
            }
        }

        return true;
    }
}
