<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

final readonly class EddingtonContext implements MilestoneContext
{
    public function __construct(
        private int $number,
    ) {
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
