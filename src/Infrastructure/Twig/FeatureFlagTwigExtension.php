<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Config\FeatureFlag;
use Twig\Attribute\AsTwigFunction;

final readonly class FeatureFlagTwigExtension
{
    #[AsTwigFunction('featureFlagIsEnabled')]
    public function featureFlagIsEnabled(string $flag): bool
    {
        return FeatureFlag::from($flag)->isEnabled();
    }
}
