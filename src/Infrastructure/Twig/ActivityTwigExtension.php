<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\EnrichedActivities;
use Twig\Attribute\AsTwigFunction;

final readonly class ActivityTwigExtension
{
    public function __construct(
        private EnrichedActivities $enrichedActivities,
    ) {
    }

    #[AsTwigFunction('getActivity')]
    public function getActivity(ActivityId $activityId): Activity
    {
        return $this->enrichedActivities->find($activityId);
    }
}
