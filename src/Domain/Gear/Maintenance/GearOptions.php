<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;

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

    public function normalizeGearIds(GearIds $normalizedGearIds): void
    {
        foreach ($this->options as $key => $option) {
            $gearId = $option[0];
            if ($gearId->isPrefixedWithStravaPrefix()) {
                continue;
            }

            foreach ($normalizedGearIds as $normalizedGearId) {
                // Try to match the gear id with the prefix.
                if (!$gearId->matches($normalizedGearId)) {
                    continue;
                }

                // If we found a match, we can replace it.
                $this->options[$key][0] = $normalizedGearId;
            }
        }
    }
}
