<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Gear\CustomGear\CustomGearRepository;
use App\Domain\Strava\Gear\ImportedGear\ImportedGearRepository;

final readonly class CombiningGearRepository implements GearRepository
{
    public function __construct(
        private ImportedGearRepository $importedGearRepository,
        private CustomGearRepository $customGearRepository,
    ) {
    }

    public function findAll(): Gears
    {
        /** @var Gears $gears */
        $gears = $this->importedGearRepository->findAll()->mergeWith(
            $this->customGearRepository->findAll()
        );

        return $gears;
    }
}
