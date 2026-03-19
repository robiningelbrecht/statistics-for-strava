<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Application\AppUrl;
use App\Domain\Activity\Activity;
use App\Domain\Activity\Image\ImageOrientation;
use App\Domain\Segment\Segment;
use App\Infrastructure\ValueObject\String\Path;
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
        return Path::from($path, $this->appUrl)->toRelativePath();
    }

    #[AsTwigFunction('placeholderImage')]
    public function placeholderImage(?ImageOrientation $imageOrientation = null): string
    {
        if (ImageOrientation::PORTRAIT === $imageOrientation) {
            return $this->toRelativeUrl('/assets/placeholder-portrait.webp');
        }

        return $this->toRelativeUrl('/assets/placeholder.webp');
    }

    #[AsTwigFilter('countryIcon')]
    public function countryIcon(string $countryCode): string
    {
        return $this->toRelativeUrl('/assets/images/flags/'.strtolower($countryCode).'.svg');
    }

    #[AsTwigFilter('activityLink', isSafe: ['html'])]
    public function renderActivityTitleLink(Activity $activity, ?int $ellipses = null, bool $truncate = false): string
    {
        $activityIcon = match (true) {
            !$activity->getSportType()->isVirtualRide() => $this->svgsTwigExtension->svgSportType($activity->getSportType()),
            $activity->isZwiftRide() => $this->svgsTwigExtension->svg('zwift-logo'),
            $activity->isRouvyRide() => $this->svgsTwigExtension->svg('rouvy-logo'),
            $activity->isMyWhooshRide() => $this->svgsTwigExtension->svg('my-whoosh-logo'),
            default => $this->svgsTwigExtension->svg('indoor-bike'),
        };

        $activityTitle = $activity->getName();

        return sprintf(
            '<a href="#" data-model-content-url="%s" class="flex items-center gap-x-1 font-medium text-blue-600 hover:underline" rel="nofollow">%s<span class="%s">%s</span></a>',
            $this->toRelativeUrl('activity/'.$activity->getId().'.html'),
            $activityIcon,
            $truncate ? 'truncate' : '',
            $ellipses ? $this->stringTwigExtension->doEllipses($activityTitle, $ellipses) : $activityTitle
        );
    }

    #[AsTwigFilter('segmentLink', isSafe: ['html'])]
    public function renderSegmentTitleLink(Segment $segment): string
    {
        $segmentIcon = match (true) {
            !$segment->getSportType()->isVirtualRide() => $this->svgsTwigExtension->svgSportType($segment->getSportType()),
            $segment->isZwiftSegment() => $this->svgsTwigExtension->svg('zwift-logo'),
            $segment->isRouvySegment() => $this->svgsTwigExtension->svg('rouvy-logo'),
            $segment->isMyWhooshSegment() => $this->svgsTwigExtension->svg('my-whoosh-logo'),
            default => $this->svgsTwigExtension->svg('indoor-bike'),
        };

        $segmentTitle = $segment->getName();

        return sprintf(
            '<a href="#" data-model-content-url="%s" class="flex items-center gap-x-1 font-medium text-blue-600 hover:underline" rel="nofollow">%s<span class="truncate">%s</span></a>',
            $this->toRelativeUrl('segment/'.$segment->getId().'.html'),
            $segmentIcon,
            $this->stringTwigExtension->doEllipses((string) $segmentTitle, 50)
        );
    }
}
