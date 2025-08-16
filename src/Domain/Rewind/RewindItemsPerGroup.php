<?php

declare(strict_types=1);

namespace App\Domain\Rewind;

final class RewindItemsPerGroup
{
    /** @var array<string, RewindItems> */
    public array $rewindItemsPerGroup = [];

    private function __construct()
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(string $group, RewindItems $items): self
    {
        $this->rewindItemsPerGroup[$group] = $items;

        return $this;
    }

    public function getForGroup(string $group): RewindItems
    {
        return $this->rewindItemsPerGroup[$group] ?? RewindItems::empty();
    }
}
