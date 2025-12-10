<?php

namespace App\Tests\Domain\Activity\Route;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Route\ActivityBasedRouteRepository;
use App\Domain\Activity\Route\RouteGeography;
use App\Domain\Activity\Route\RouteRepository;
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
                ->withRouteGeography(RouteGeography::create(['country_code' => 'BE']))
                ->build(),
            rawData: []
        ));

        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline('')
                ->withRouteGeography(RouteGeography::create(['waw']))
                ->build(),
            rawData: []
        ));
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline(null)
                ->withRouteGeography(RouteGeography::create(['waw']))
                ->build(),
            rawData: []
        ));
        $this->activityWithRawDataRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
                ->withPolyline('line')
                ->withRouteGeography(null)
                ->build(),
            rawData: []
        ));

        $this->assertMatchesJsonSnapshot(Json::encode($this->routeRepository->findAll()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->activityWithRawDataRepository = $this->getContainer()->get(ActivityWithRawDataRepository::class);
        $this->routeRepository = new ActivityBasedRouteRepository(
            $this->getConnection(),
        );
    }
}
