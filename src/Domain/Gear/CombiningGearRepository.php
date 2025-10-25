<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Domain\Gear\CustomGear\CustomGearRepository;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;

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

    public function hasGear(): bool
    {
        $gear = $this->importedGearRepository->findAll();
        if (!$gear->isEmpty()) {
            return true;
        }

        return !$this->customGearRepository->findAll()->isEmpty();
    }
}
