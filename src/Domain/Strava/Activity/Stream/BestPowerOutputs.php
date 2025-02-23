<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

final class BestPowerOutputs implements \IteratorAggregate
{
    /** @var array<int, array<mixed>> */
    private array $items = [];

    private function __construct(
    ) {
    }

    public static function empty(
    ): self {
        return new self();
    }

    public function add(string $description, PowerOutputs $powerOutputs): self
    {
        if ($powerOutputs->isEmpty()) {
            return $this;
        }

        $this->items[] = [$description, $powerOutputs];

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
