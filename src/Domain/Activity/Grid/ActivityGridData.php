<?php

namespace App\Domain\Activity\Grid;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityGridData implements \JsonSerializable
{
    /** @var array<int, array{0: string, 1: int}> */
    private array $data = [];

    private function __construct(
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function add(SerializableDateTime $on, int $value): void
    {
        $this->data[] = [$on->format('Y-m-d'), $value];
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
