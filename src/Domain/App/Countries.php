<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use Doctrine\DBAL\Connection;
use Symfony\Component\Intl\Countries as SymfonyCountries;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class Countries
{
    public function __construct(
        private Connection $connection,
        private LocaleSwitcher $localeSwitcher,
    )
    {
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
        )->fetchAllAssociative();

        $countries = [];
        foreach($results as $result){
            $countryCode = $result['countryCode'];
            $countries[$countryCode] = SymfonyCountries::getName(
                country: strtoupper($countryCode),
                displayLocale: $this->localeSwitcher->getLocale()
            );
        }

        return $countries;
    }
}