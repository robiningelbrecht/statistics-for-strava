<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\DeleteOrphanedSegments;

use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteOrphanedSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private SegmentRepository $segmentRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteOrphanedSegments);

        $this->segmentRepository->deleteOrphaned();
    }
}
