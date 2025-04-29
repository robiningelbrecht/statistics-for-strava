<?php

namespace App\Tests\Domain\Strava\Activity\Split\ImportActivitySplits;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Activity\Split\ActivitySplitRepository;
use App\Domain\Strava\Activity\Split\ImportActivitySplits\ImportActivitySplits;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Split\ActivitySplitBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitySplitsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-splits-not-imported'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            Json::decode('{"prefer_perceived_exertion":null,"splits_metric":[{"distance":1000.7,"elapsed_time":1215,"elevation_difference":131.2,"moving_time":895,"split":1,"average_speed":1.12,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.1,"elapsed_time":1052,"elevation_difference":60.1,"moving_time":912,"split":2,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.4,"elapsed_time":1336,"elevation_difference":143.1,"moving_time":982,"split":3,"average_speed":1.02,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":996.9,"elapsed_time":1010,"elevation_difference":78.1,"moving_time":935,"split":4,"average_speed":1.07,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1003.1,"elapsed_time":1968,"elevation_difference":-95.2,"moving_time":924,"split":5,"average_speed":1.09,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.2,"elapsed_time":1034,"elevation_difference":-62.8,"moving_time":839,"split":6,"average_speed":1.19,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.4,"elapsed_time":3037,"elevation_difference":-71.2,"moving_time":941,"split":7,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1127,"elevation_difference":8.9,"moving_time":884,"split":8,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.6,"elapsed_time":1683,"elevation_difference":-5.4,"moving_time":1065,"split":9,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.8,"elapsed_time":1233,"elevation_difference":76.3,"moving_time":1116,"split":10,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1164,"elevation_difference":69.8,"moving_time":1125,"split":11,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":1974,"elevation_difference":78.7,"moving_time":965,"split":12,"average_speed":1.04,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":997.4,"elapsed_time":901,"elevation_difference":-85.5,"moving_time":794,"split":13,"average_speed":1.26,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.9,"elapsed_time":1102,"elevation_difference":-137,"moving_time":975,"split":14,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":973,"elevation_difference":-68.3,"moving_time":728,"split":15,"average_speed":1.37,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000,"elapsed_time":1162,"elevation_difference":-113.2,"moving_time":945,"split":16,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":0,"elapsed_time":2,"elevation_difference":-0.2,"moving_time":2,"split":17,"average_speed":0,"average_grade_adjusted_speed":null,"pace_zone":0}],"splits_standard":[{"distance":1611.6,"elapsed_time":1759,"elevation_difference":166.2,"moving_time":1422,"split":1,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.2,"elapsed_time":2086,"elevation_difference":193.3,"moving_time":1567,"split":2,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.8,"elapsed_time":2543,"elevation_difference":-17.2,"moving_time":1523,"split":3,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.9,"elapsed_time":1712,"elevation_difference":-133.9,"moving_time":1371,"split":4,"average_speed":1.17,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1610.8,"elapsed_time":3719,"elevation_difference":-13.7,"moving_time":1469,"split":5,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.3,"elapsed_time":2421,"elevation_difference":28.7,"moving_time":1715,"split":6,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609,"elapsed_time":1913,"elevation_difference":141.1,"moving_time":1828,"split":7,"average_speed":0.88,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609.5,"elapsed_time":2473,"elevation_difference":-25.7,"moving_time":1389,"split":8,"average_speed":1.16,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1611.6,"elapsed_time":1694,"elevation_difference":-176.2,"moving_time":1432,"split":9,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1513.6,"elapsed_time":1653,"elevation_difference":-155.2,"moving_time":1311,"split":10,"average_speed":1.15,"average_grade_adjusted_speed":null,"pace_zone":0}]}')
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-splits-but-already-imported'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-10'))
                ->build(),
            Json::decode('{"prefer_perceived_exertion":null,"splits_metric":[{"distance":1000.7,"elapsed_time":1215,"elevation_difference":131.2,"moving_time":895,"split":1,"average_speed":1.12,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.1,"elapsed_time":1052,"elevation_difference":60.1,"moving_time":912,"split":2,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.4,"elapsed_time":1336,"elevation_difference":143.1,"moving_time":982,"split":3,"average_speed":1.02,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":996.9,"elapsed_time":1010,"elevation_difference":78.1,"moving_time":935,"split":4,"average_speed":1.07,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1003.1,"elapsed_time":1968,"elevation_difference":-95.2,"moving_time":924,"split":5,"average_speed":1.09,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.2,"elapsed_time":1034,"elevation_difference":-62.8,"moving_time":839,"split":6,"average_speed":1.19,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.4,"elapsed_time":3037,"elevation_difference":-71.2,"moving_time":941,"split":7,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1127,"elevation_difference":8.9,"moving_time":884,"split":8,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.6,"elapsed_time":1683,"elevation_difference":-5.4,"moving_time":1065,"split":9,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":998.8,"elapsed_time":1233,"elevation_difference":76.3,"moving_time":1116,"split":10,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1001.3,"elapsed_time":1164,"elevation_difference":69.8,"moving_time":1125,"split":11,"average_speed":0.89,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":1974,"elevation_difference":78.7,"moving_time":965,"split":12,"average_speed":1.04,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":997.4,"elapsed_time":901,"elevation_difference":-85.5,"moving_time":794,"split":13,"average_speed":1.26,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":999.9,"elapsed_time":1102,"elevation_difference":-137,"moving_time":975,"split":14,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000.1,"elapsed_time":973,"elevation_difference":-68.3,"moving_time":728,"split":15,"average_speed":1.37,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1000,"elapsed_time":1162,"elevation_difference":-113.2,"moving_time":945,"split":16,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":0,"elapsed_time":2,"elevation_difference":-0.2,"moving_time":2,"split":17,"average_speed":0,"average_grade_adjusted_speed":null,"pace_zone":0}],"splits_standard":[{"distance":1611.6,"elapsed_time":1759,"elevation_difference":166.2,"moving_time":1422,"split":1,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.2,"elapsed_time":2086,"elevation_difference":193.3,"moving_time":1567,"split":2,"average_speed":1.03,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.8,"elapsed_time":2543,"elevation_difference":-17.2,"moving_time":1523,"split":3,"average_speed":1.06,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.9,"elapsed_time":1712,"elevation_difference":-133.9,"moving_time":1371,"split":4,"average_speed":1.17,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1610.8,"elapsed_time":3719,"elevation_difference":-13.7,"moving_time":1469,"split":5,"average_speed":1.1,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1608.3,"elapsed_time":2421,"elevation_difference":28.7,"moving_time":1715,"split":6,"average_speed":0.94,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609,"elapsed_time":1913,"elevation_difference":141.1,"moving_time":1828,"split":7,"average_speed":0.88,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1609.5,"elapsed_time":2473,"elevation_difference":-25.7,"moving_time":1389,"split":8,"average_speed":1.16,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1611.6,"elapsed_time":1694,"elevation_difference":-176.2,"moving_time":1432,"split":9,"average_speed":1.13,"average_grade_adjusted_speed":null,"pace_zone":0},{"distance":1513.6,"elapsed_time":1653,"elevation_difference":-155.2,"moving_time":1311,"split":10,"average_speed":1.15,"average_grade_adjusted_speed":null,"pace_zone":0}]}')
        ));
        $this->getContainer()->get(ActivitySplitRepository::class)->add(
            ActivitySplitBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('with-splits-but-already-imported'))
                ->withUnitSystem(UnitSystem::IMPERIAL)
                ->withSplitNumber(3)
                ->build()
        );
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('without-splits'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-09'))
                ->build(),
            []
        ));

        $this->commandBus->dispatch(new ImportActivitySplits($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
