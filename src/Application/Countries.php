<?php

declare(strict_types=1);

namespace App\Application;

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
            SELECT DISTINCT LOWER(JSON_EXTRACT(location, '$.country_code')) as countryCode
            FROM Activity
            WHERE JSON_EXTRACT(location, '$.country_code') IS NOT NULL
            SQL
        )->fetchFirstColumn();

        return $this->hydrate($results);
    }

    /**
     * @return array<string, string>
     */
    public function getUsedInPhotos(): array
    {
        $results = $this->connection->executeQuery(
            <<<SQL
            SELECT DISTINCT LOWER(JSON_EXTRACT(location, '$.country_code')) as countryCode
            FROM Activity
            WHERE JSON_EXTRACT(location, '$.country_code') IS NOT NULL
            AND totalImageCount > 0
            SQL
        )->fetchFirstColumn();

        return $this->hydrate($results);
    }

    /**
     * @return array<string, string>
     */
    public function getUsedInSegments(): array
    {
        $results = $this->connection->executeQuery(
            <<<SQL
            SELECT DISTINCT LOWER(countryCode)
            FROM Segment
            WHERE countryCode IS NOT NULL
            SQL
        )->fetchFirstColumn();

        return $this->hydrate($results);
    }

    public function findCountryCodeByCountryName(string $countryName): ?string
    {
        if (!$countryCode = array_search($countryName, $this->countriesKeyedByAlpha2codes)) {
            return null;
        }

        return $countryCode;
    }

    /**
     * @param string[] $results
     *
     * @return array<string, string>
     */
    private function hydrate(array $results): array
    {
        $countries = [];
        foreach ($results as $countryCode) {
            if ('xk' === strtolower((string) $countryCode)) {
                // Currently Symfony does not support Kosovo as a country.
                // Need to wait until this commit is released in Symfony 7.4:
                // https://github.com/symfony/symfony/issues/40020
                $countries[$countryCode] = 'Kosovo';
                continue;
            }
            $countries[$countryCode] = SymfonyCountries::getName(
                country: strtoupper((string) $countryCode),
                displayLocale: $this->localeSwitcher->getLocale()
            );
        }

        return $countries;
    }
}
