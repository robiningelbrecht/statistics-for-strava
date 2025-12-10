<?php

namespace App\Domain\Activity\Route;

final readonly class RouteGeography
{
    public function __construct(
        /** @var string[] */
        private array $passedThroughCountries,
    ) {
    }

    /**
     * @return string[]
     */
    public function getPassedThroughCountries(): array
    {
        return $this->passedThroughCountries;
    }
}
