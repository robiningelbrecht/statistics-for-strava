<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindLongestActivity;

use App\Domain\Activity\Activity;
use App\Infrastructure\CQRS\Query\Response;

final readonly class FindLongestActivityResponse implements Response
{
    public function __construct(
        private Activity $activity,
    ) {
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }
}
