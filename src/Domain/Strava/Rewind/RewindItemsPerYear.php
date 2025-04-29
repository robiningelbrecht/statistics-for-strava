<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind;

use App\Infrastructure\ValueObject\Time\Year;

final class RewindItemsPerYear
{
    /** @var array<int, RewindItems> */
    public array $rewindItemsPerYear = [];

    private function __construct()
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(Year $rewindYear, RewindItems $items): self
    {
        $this->rewindItemsPerYear[$rewindYear->toInt()] = $items;

        return $this;
    }

    public function getForYear(Year $year): RewindItems
    {
        return $this->rewindItemsPerYear[$year->toInt()] ?? RewindItems::empty();
    }
}
