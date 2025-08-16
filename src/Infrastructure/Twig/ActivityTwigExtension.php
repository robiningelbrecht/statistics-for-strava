<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Activity\Activity;
use Twig\Attribute\AsTwigFilter;

final readonly class ActivityTwigExtension
{
    public function __construct(
        private UrlTwigExtension $urlTwigExtension,
        private SvgsTwigExtension $svgsTwigExtension,
        private StringTwigExtension $stringTwigExtension,
    ) {
    }

    #[AsTwigFilter('renderTitle', isSafe: ['html'])]
    public function renderTitle(Activity $activity, ?int $ellipses = null, bool $truncate = false): string
    {
        if ($activity->getSportType()->isVirtualRide()) {
            if ($activity->isZwiftRide()) {
                $activityIcon = $this->svgsTwigExtension->svg('zwift-logo');
            } elseif ($activity->isRouvyRide()) {
                $activityIcon = $this->svgsTwigExtension->svg('rouvy-logo');
            } else {
                $activityIcon = $this->svgsTwigExtension->svg('indoor-bike');
            }
        } else {
            $activityIcon = $this->svgsTwigExtension->svgSportType($activity->getSportType());
        }

        $activityTitle = $activity->getName();

        return sprintf(
            '<a href="#" data-model-content-url="%s" class="flex items-center gap-x-1 font-medium text-blue-600 hover:underline" rel="nofollow">%s<span class="%s">%s</span></a>',
            $this->urlTwigExtension->toRelativeUrl('activity/'.$activity->getId().'.html'),
            $activityIcon,
            $truncate ? 'truncate' : '',
            $ellipses ? $this->stringTwigExtension->doEllipses($activityTitle, $ellipses) : $activityTitle
        );
    }
}
