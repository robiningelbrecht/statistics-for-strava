<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance;

use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIds;

final class GearOptions
{
    /** @var array<int, array{0: GearId, 1: string}> */
    private array $options = [];

    private function __construct(
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return empty($this->options);
    }

    public function add(GearId $gearId, string $imgSrc): void
    {
        $this->options[] = [$gearId, $imgSrc];
    }

    /**
     * @return array<int, array{0: GearId, 1: string}>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getAllReferencedGearIds(): GearIds
    {
        return GearIds::fromArray(array_column($this->getOptions(), 0));
    }

    public function getImageReferenceForGear(GearId $gearId): ?string
    {
        foreach ($this->options as $option) {
            if ($option[0] == $gearId) {
                return $option[1];
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getAllReferencedImages(): array
    {
        return array_column($this->getOptions(), 1);
    }
}
