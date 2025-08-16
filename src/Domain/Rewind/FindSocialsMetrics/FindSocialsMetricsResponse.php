<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindSocialsMetrics;

use App\Infrastructure\CQRS\Query\Response;

final readonly class FindSocialsMetricsResponse implements Response
{
    public function __construct(
        private int $kudoCount,
        private int $commentCount,
    ) {
    }

    public function getKudoCount(): int
    {
        return $this->kudoCount;
    }

    public function getCommentCount(): int
    {
        return $this->commentCount;
    }
}
