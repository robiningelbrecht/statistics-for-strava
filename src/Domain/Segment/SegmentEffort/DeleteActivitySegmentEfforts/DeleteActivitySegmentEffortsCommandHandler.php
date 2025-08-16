<?php

declare(strict_types=1);

namespace App\Domain\Segment\SegmentEffort\DeleteActivitySegmentEfforts;

use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

final readonly class DeleteActivitySegmentEffortsCommandHandler implements CommandHandler
{
    public function __construct(
        private SegmentEffortRepository $segmentEffortRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof DeleteActivitySegmentEfforts);

        $this->segmentEffortRepository->deleteForActivity($command->getActivityId());
    }
}
