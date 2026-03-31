<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use Symfony\Component\Intl\Countries;

final readonly class FirstActivityInCountryContext implements MilestoneContext
{
    public function __construct(
        private string $countryCode,
        private string $activityName,
    ) {
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getCountryName(): string
    {
        $code = strtoupper($this->countryCode);
        if ('XK' === $code) {
            return 'Kosovo';
        }

        return Countries::getName($code);
    }

    public function getActivityName(): string
    {
        return $this->activityName;
    }
}
