<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement;

trait MeasurementFromFloat
{
    private function __construct(
        private readonly float $value,
    ) {
    }

    public static function from(float $value): self
    {
        return new self($value);
    }

    public function isZeroOrLower(): bool
    {
        return $this->value <= 0;
    }

    public function isLowerThanOne(): bool
    {
        return $this->value < 1;
    }

    public static function zero(): self
    {
        return self::from(0);
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function toInt(): int
    {
        return (int) $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->toFloat();
    }

    public function jsonSerialize(): float
    {
        return $this->toFloat();
    }
}
