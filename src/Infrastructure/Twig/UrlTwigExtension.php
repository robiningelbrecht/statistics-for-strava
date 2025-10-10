<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\BuildApp\AppUrl;
use App\Domain\Activity\Activity;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final readonly class UrlTwigExtension
{
    public function __construct(
        private AppUrl $appUrl,
        private StringTwigExtension $stringTwigExtension,
        private SvgsTwigExtension $svgsTwigExtension,
    ) {
    }

    #[AsTwigFunction('relativeUrl')]
    public function toRelativeUrl(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        if (null === $this->appUrl->getBasePath()) {
            return $path;
        }

        return '/'.trim($this->appUrl->getBasePath(), '/').$path;
    }

    #[AsTwigFunction('placeholderImage')]
    public function placeholderImage(): string
    {
        return $this->toRelativeUrl('/assets/placeholder.webp');
    }

    #[AsTwigFilter('activityLink', isSafe: ['html'])]
    public function renderActivityTitleLink(Activity $activity, ?int $ellipses = null, bool $truncate = false): string
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
            $this->toRelativeUrl('activity/'.$activity->getId().'.html'),
            $activityIcon,
            $truncate ? 'truncate' : '',
            $ellipses ? $this->stringTwigExtension->doEllipses($activityTitle, $ellipses) : $activityTitle
        );
    }
}
