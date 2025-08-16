<?php

namespace App\Tests\Domain\Activity\Route;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Route\ActivityBasedRouteRepository;
use App\Domain\Activity\Route\RouteRepository;
use App\Domain\Integration\Geocoding\Nominatim\Location;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use Spatie\Snapshots\MatchesSnapshots;

class ActivityBasedRouteRepositoryTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private RouteRepository $routeRepository;
    private ActivityWithRawDataRepository $activityWithRawDataRepository;

    public function testFindAll(): void
    {
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline('line')
                ->withLocation(Location::fromState(['country_code' => 'BE']))
                ->build(),
            rawData: []
        ));

        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline('')
                ->withLocation(Location::fromState(['waw']))
                ->build(),
            rawData: []
        ));
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline(null)
                ->withLocation(Location::fromState(['waw']))
                ->build(),
            rawData: []
        ));
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline('line')
                ->withLocation(null)
                ->build(),
            rawData: []
        ));

        $this->assertMatchesJsonSnapshot(Json::encode($this->routeRepository->findAll()));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityWithRawDataRepository = $this->getContainer()->get(ActivityWithRawDataRepository::class);
        $this->routeRepository = new ActivityBasedRouteRepository(
            $this->getConnection(),
        );
    }
}
