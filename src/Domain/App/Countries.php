<?php

declare(strict_types=1);

namespace App\Domain\App;

use Doctrine\DBAL\Connection;
use Symfony\Component\Intl\Countries as SymfonyCountries;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class Countries
{
    /** @var string[] */
    private array $countriesKeyedByAlpha2codes;

    public function __construct(
        private Connection $connection,
        private LocaleSwitcher $localeSwitcher,
    ) {
        $this->countriesKeyedByAlpha2codes = SymfonyCountries::getNames();
    }

    /**
     * @return array<string, string>
     */
    public function getUsedInActivities(): array
    {
        $results = $this->connection->executeQuery(
            <<<SQL
            SELECT DISTINCT JSON_EXTRACT(location, '$.country_code') as countryCode
            FROM Activity
            WHERE JSON_EXTRACT(location, '$.country_code') IS NOT NULL
            SQL
        )->fetchFirstColumn();

        $countries = [];
        foreach ($results as $countryCode) {
            $countries[$countryCode] = SymfonyCountries::getName(
                country: strtoupper($countryCode),
                displayLocale: $this->localeSwitcher->getLocale()
            );
        }

        return $countries;
    }

    /**
     * @return array<string, string>
     */
    public function getUsedInSegments(): array
    {
        $results = $this->connection->executeQuery(
            <<<SQL
            SELECT DISTINCT countryCode
            FROM Segment
            WHERE countryCode IS NOT NULL
            SQL
        )->fetchFirstColumn();

        $countries = [];
        foreach ($results as $countryCode) {
            $countries[$countryCode] = SymfonyCountries::getName(
                country: strtoupper($countryCode),
                displayLocale: $this->localeSwitcher->getLocale()
            );
        }

        return $countries;
    }

    public function findCountryCodeByCountryName(string $countryName): ?string
    {
        if (!$countryCode = array_search($countryName, $this->countriesKeyedByAlpha2codes)) {
            return null;
        }

        return $countryCode;
    }
}
