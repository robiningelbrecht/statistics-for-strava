<?php

declare(strict_types=1);

namespace App\Application\Import\ImportGear;

use App\Domain\Gear\Gear;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;

final readonly class GearImportStatus
{
    public function __construct(
        private ImportedGearRepository $importedGearRepository,
    ) {
    }

    public function isComplete(): bool
    {
        $stravaGearIdsOnActivities = $this->importedGearRepository->findUniqueStravaGearIds(null);
        $importedGears = $this->importedGearRepository->findAll();

        foreach ($stravaGearIdsOnActivities as $gearId) {
            if (!$importedGears->getByGearId($gearId) instanceof Gear) {
                return false;
            }
        }

        return true;
    }
}
