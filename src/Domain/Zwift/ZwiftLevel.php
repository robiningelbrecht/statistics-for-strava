<?php

declare(strict_types=1);

namespace App\Domain\Zwift;

use App\Infrastructure\ValueObject\Number\PositiveInteger;

final readonly class ZwiftLevel extends PositiveInteger
{
    private const int MAX_LEVEL = 100;

    protected function validate(int $value): void
    {
        if ($value >= 1 && $value <= self::MAX_LEVEL) {
            return;
        }

        throw new \InvalidArgumentException('ZwiftLevel must be a number between 1 and 100');
    }

    public function getProgressPercentage(): float
    {
        $xpNeeded = $this->getXpNeededToReachLevel();
        if (0 === $xpNeeded) {
            return 1;
        }

        $percentage = round($this->getXpNeededToReachLevel() / $this->getXpNeededToReachMaxLevel() * 100, 2);
        if ($percentage > 96 && $percentage < 100) {
            // Visually percentages between 97 and 99 are "ugly".
            // So we set them to 96% to make it look better.
            return 96;
        }

        return $percentage;
    }

    public function getXpNeededToReachLevel(): int
    {
        return $this->getXpTable()[$this->getValue() - 1];
    }

    public function getXpNeededToReachMaxLevel(): int
    {
        $xpTable = $this->getXpTable();

        /** @var int $maxXp */
        $maxXp = end($xpTable);

        return $maxXp;
    }

    /**
     * @return int[]
     */
    private function getXpTable(): array
    {
        return [
            0, 750, 1500, 2500, 3500, 5000, 6500, 8000, 9500, 11000, // 1–10
            13000, 15000, 17000, 19000, 21000, 23500, 26000, 28500, 31000, 33500, // 11–20
            36500, 39500, 42500, 45500, 48500, 52000, 55500, 59500, 64000, 68500, // 21–30
            73000, 78500, 84000, 89500, 95000, 101500, 108000, 114500, 121000, 127500, // 31–40
            134500, 142500, 150500, 158500, 166500, 175500, 184500, 193500, 202500, 212000, // 41–50
            221500, 231000, 240500, 250000, 260000, 270000, 280000, 290000, 300000, 310500, // 51–60
            321000, 331500, 342000, 352500, 363500, 374500, 385500, 396500, 407500, 418500, // 61–70
            429500, 441000, 452500, 464000, 475500, 487000, 498500, 510000, 522000, 534000, // 71–80
            546000, 558000, 570000, 582000, 594000, 606500, 619000, 631500, 644000, 657000, // 81–90
            670000, 683500, 697000, 711000, 725000, 740000, 755000, 771000, 787000, 807000,  // 91–100
        ];
    }
}
