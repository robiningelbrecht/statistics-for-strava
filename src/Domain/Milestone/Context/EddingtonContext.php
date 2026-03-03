<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

final readonly class EddingtonContext implements MilestoneContext
{
    public function __construct(
        private string $label,
        private int $number,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}
