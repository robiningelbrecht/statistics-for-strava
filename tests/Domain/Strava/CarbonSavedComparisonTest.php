<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\CarbonSavedComparison;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class CarbonSavedComparisonTest extends TestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'provideComparisonData')]
    public function testGetCapsForPetBottleComparison(Kilogram $carbonSavedInKg): void
    {
        $carbonSavedComparison = CarbonSavedComparison::create($carbonSavedInKg);

        $this->assertMatchesJsonSnapshot([
            'petBottleComparison' => $carbonSavedComparison->getPetBottleComparison(),
            'capsForPetBottleComparison' => $carbonSavedComparison->getCapsForPetBottleComparison(),
            'aluminiumCanComparison' => $carbonSavedComparison->getAluminiumCanComparison(),
            'reusableGlassBottleComparison' => $carbonSavedComparison->getReusableGlassBottleComparison(),
            'annualTreeCO2Comparison' => $carbonSavedComparison->getAnnualTreeCO2Comparison(),
            'drivingACarComparison' => $carbonSavedComparison->getDrivingACarComparison(),
            'economyFlightsZurichLondonComparison' => $carbonSavedComparison->getEconomyFlightsZurichLondonComparison(),
            'googleSearchesComparison' => $carbonSavedComparison->getGoogleSearchesComparison(),
            'manufacturedSmartphonesComparison' => $carbonSavedComparison->getManufacturedSmartphonesComparison(),
            'portionsOfSpaghettiComparison' => $carbonSavedComparison->getPortionsOfSpaghettiComparison(),
        ]);
    }

    public static function provideComparisonData(): array
    {
        return [
            [Kilogram::from(9000)],
            [Kilogram::from(1)],
        ];
    }
}
