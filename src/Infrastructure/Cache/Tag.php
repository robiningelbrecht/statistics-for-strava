<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

final readonly class Tag implements \Stringable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function activity(string $activityId): self
    {
        return new self('activity:'.$activityId);
    }

    public static function segment(string $segmentId): self
    {
        return new self('segment:'.$segmentId);
    }

    public static function bestEffort(int $distanceInMeter, string $sportType): self
    {
        return new self(sprintf('best-effort:%d:%s', $distanceInMeter, $sportType));
    }

    public static function challenges(): self
    {
        return new self('challenges');
    }

    public static function gear(): self
    {
        return new self('gear');
    }

    public static function athlete(): self
    {
        return new self('athlete');
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
