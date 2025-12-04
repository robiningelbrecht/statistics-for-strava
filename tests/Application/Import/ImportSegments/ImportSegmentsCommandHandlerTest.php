<?php

namespace App\Tests\Application\Import\ImportSegments;

use App\Application\Import\ImportSegments\ImportSegments;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Segment\SegmentBuilder;
use App\Tests\Domain\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportSegmentsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withName('Test activity 1')
                ->withDeviceName('Zwift')
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '1',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Segment Effort One',
                        'elapsed_time' => 300,
                        'segment' => [
                            'id' => '1',
                            'name' => 'Segment One',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                        ],
                    ],
                ],
            ],
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withName('Test activity 2')
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '2',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Segment Effort Two',
                        'segment' => [
                            'id' => '1',
                            'name' => 'Segment One',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                        ],
                    ],
                ],
            ],
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(3))
                ->withName('Test activity 3')
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(4))
                ->withName('Test activity 4')
                ->withStartDateTime(SerializableDateTime::fromString('2024-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '3',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Segment Effort Two',
                        'segment' => [
                            'id' => '2',
                            'name' => 'Segment Two',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                            'starred' => true,
                        ],
                    ],
                ],
            ],
        ));
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(2))
                ->withSegmentId(SegmentId::fromUnprefixed('1'))
                ->withActivityId(ActivityId::fromUnprefixed(9542782314))
                ->withElapsedTimeInSeconds(9.3)
                ->withAverageWatts(200)
                ->withDistance(Kilometer::from(0.1))
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withSegmentEffortId(SegmentEffortId::fromUnprefixed(3))
                ->withSegmentId(SegmentId::fromUnprefixed('2'))
                ->withActivityId(ActivityId::fromUnprefixed(9542782314))
                ->withElapsedTimeInSeconds(9.3)
                ->withAverageWatts(200)
                ->withDistance(Kilometer::from(0.1))
                ->build()
        );
        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed('2'))
                ->withIsFavourite(false)
                ->build()
        );

        $this->commandBus->dispatch(new ImportSegments($output));
        $this->assertMatchesTextSnapshot($output);

        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM Segment')->fetchAllAssociative()
        );
        $this->assertMatchesJsonSnapshot(
            $this->getConnection()->executeQuery('SELECT * FROM SegmentEffort')->fetchAllAssociative()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test"}']
        );
    }
}
