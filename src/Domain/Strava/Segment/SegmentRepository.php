<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\Repository\Pagination;

interface SegmentRepository
{
    public function add(Segment $segment): void;

    public function update(Segment $segment): void;

    public function find(SegmentId $segmentId): Segment;

    public function findAll(Pagination $pagination): Segments;

    public function count(): int;

    public function deleteOrphaned(): void;
}
