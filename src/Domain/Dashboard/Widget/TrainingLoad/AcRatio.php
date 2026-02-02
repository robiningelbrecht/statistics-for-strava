<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget\TrainingLoad;

final readonly class AcRatio
{
    private AcRatioStatus $status;

    private function __construct(
        public float $value,
    ) {
        $this->status = AcRatioStatus::fromFloat($this->value);
    }

    public static function of(float $value): self
    {
        return new self($value);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getStatus(): AcRatioStatus
    {
        return $this->status;
    }
}
