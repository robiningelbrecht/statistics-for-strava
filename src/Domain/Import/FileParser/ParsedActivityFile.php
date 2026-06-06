<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

use App\Domain\Activity\Activity;
use App\Domain\Activity\Lap\ActivityLaps;
use App\Domain\Activity\Stream\ActivityStreams;

final readonly class ParsedActivityFile
{
    private function __construct(
        private Activity $activity,
        private ActivityStreams $streams,
        private ActivityLaps $laps,
    ) {
    }

    public static function create(
        Activity $activity,
        ActivityStreams $streams,
        ActivityLaps $laps,
    ): self {
        return new self(
            activity: $activity,
            streams: $streams,
            laps: $laps,
        );
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    public function getStreams(): ActivityStreams
    {
        return $this->streams;
    }

    public function getLaps(): ActivityLaps
    {
        return $this->laps;
    }
}
