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
        return $this->importedGearRepository->findAll()->mergeWith(
            $this->customGearRepository->findAll()
        );
    }

    public function findAllUsed(): Gears
    {
        return $this->importedGearRepository->findAllUsed()->mergeWith(
            $this->customGearRepository->findAllUsed()
        );
    }

    public function hasGear(): bool
    {
        $gear = $this->importedGearRepository->findAllUsed();
        if (!$gear->isEmpty()) {
            return true;
        }

        return !$this->customGearRepository->findAllUsed()->isEmpty();
    }
}
