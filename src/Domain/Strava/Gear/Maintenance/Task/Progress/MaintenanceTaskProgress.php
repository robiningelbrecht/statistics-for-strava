<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\Task\Progress;

final readonly class MaintenanceTaskProgress
{
    private function __construct(
        private int $percentage,
        private string $description,
    ) {
        if ($this->percentage < 0 || $this->percentage > 100) {
            throw new \InvalidArgumentException('Percentage must be between 0 and 100');
        }
    }

    public static function from(int $percentage, string $description): self
    {
        return new self(
            percentage: $percentage,
            description: $description
        );
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
