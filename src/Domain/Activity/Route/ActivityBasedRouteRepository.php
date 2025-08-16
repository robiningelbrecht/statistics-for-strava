<?php

declare(strict_types=1);

namespace App\Domain\Activity\Route;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Integration\Geocoding\Nominatim\Location;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\ArrayParameterType;

final readonly class ActivityBasedRouteRepository extends DbalRepository implements RouteRepository
{
    public function findAll(): Routes
    {
        $query = 'SELECT polyline, location, sportType, startDateTime
                    FROM Activity
                    WHERE sportType IN (:sportTypes)
                    AND polyline IS NOT NULL AND polyline <> ""
                    AND location IS NOT NULL AND location <> ""';

        $results = $this->connection->executeQuery(
            sql: $query,
            params: [
                'sportTypes' => array_map(
                    fn (SportType $sportType) => $sportType->value,
                    array_filter(
                        SportType::cases(),
                        fn (SportType $sportType) => $sportType->supportsReverseGeocoding()
                    )
                ),
            ],
            types: [
                'sportTypes' => ArrayParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $routes = Routes::empty();
        foreach ($results as $result) {
            $routes->add(Route::create(
                encodedPolyline: $result['polyline'],
                location: Location::fromState(Json::decode($result['location'])),
                sportType: SportType::from($result['sportType']),
                on: SerializableDateTime::fromString($result['startDateTime'])
            ));
        }

        return $routes;
    }
}
