<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindTotalActivityCount;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindTotalActivityCountResponse implements Response
{
    public function __construct(
        private int $totalActivityCount,
    ) {
    }

    public function getTotalActivityCount(): int
    {
        return $this->totalActivityCount;
    }
}
