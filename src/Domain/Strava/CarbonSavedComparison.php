<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;

final readonly class CarbonSavedComparison
{
    private function __construct(
        private Kilogram $carbonSavedInKg,
    ) {
    }

    public static function create(Kilogram $carbonsSavedInKg): self
    {
        return new self($carbonsSavedInKg);
    }

    public function getPetBottleComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / 0.067);
    }

    public function getCapsForPetBottleComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / (5.8 / 1000));
    }

    public function getAluminiumCanComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / (79 / 1000));
    }

    public function getReusableGlassBottleComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / (19 / 1000));
    }

    public function getAnnualTreeCO2Comparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / 22);
    }

    public function getDrivingACarComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() * 3.7);
    }

    public function getEconomyFlightsZurichLondonComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / 186);
    }

    public function getGoogleSearchesComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / (0.2 / 1000));
    }

    public function getManufacturedSmartphonesComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / 80);
    }

    public function getPortionsOfSpaghettiComparison(): int
    {
        return (int) round($this->carbonSavedInKg->toFloat() / 0.63);
    }
}
