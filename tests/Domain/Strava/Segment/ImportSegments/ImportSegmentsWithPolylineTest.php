<?php

namespace App\Tests\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityWithRawData;
use App\Domain\Strava\Activity\ActivityWithRawDataRepository;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\SpyOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ImportSegmentsWithPolylineTest extends ContainerTestCase
{
    private CommandBus $commandBus;
    private MockObject $strava;
    private MockObject $logger;
    private SegmentRepository $segmentRepository;

    public function testHandleWithPolylineData(): void
    {
        $output = new SpyOutput();
        
        // Setup activity with segment efforts
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withName('Test activity with segment')
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '1',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Test Segment Effort',
                        'elapsed_time' => 300,
                        'segment' => [
                            'id' => '123',
                            'name' => 'Test Segment',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                        ],
                    ],
                ],
            ],
        ));

        // Mock Strava API to return segment with polyline
        $this->strava
            ->expects($this->once())
            ->method('getSegment')
            ->willReturn([
                'id' => 123,
                'name' => 'Test Segment',
                'map' => [
                    'polyline' => 'encodedPolylineData123'
                ]
            ]);

        // Expect logging for successful polyline fetch
        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->commandBus->dispatch(new ImportSegments($output));

        // Verify segment was created with polyline
        $segment = $this->segmentRepository->find(\App\Domain\Strava\Segment\SegmentId::fromUnprefixed('123'));
        $this->assertEquals('encodedPolylineData123', $segment->getPolyline());
    }

    public function testHandleWithStravaApiError(): void
    {
        $output = new SpyOutput();
        
        // Setup activity with segment efforts
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withName('Test activity with segment')
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '1',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Test Segment Effort',
                        'elapsed_time' => 300,
                        'segment' => [
                            'id' => '456',
                            'name' => 'Test Segment',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                        ],
                    ],
                ],
            ],
        ));

        // Mock Strava API to throw an exception
        $this->strava
            ->expects($this->once())
            ->method('getSegment')
            ->willThrowException(new \RuntimeException('API Error'));

        // Expect warning logging for API failure
        $this->logger
            ->expects($this->once())
            ->method('warning');

        $this->commandBus->dispatch(new ImportSegments($output));

        // Verify segment was created without polyline
        $segment = $this->segmentRepository->find(\App\Domain\Strava\Segment\SegmentId::fromUnprefixed('456'));
        $this->assertNull($segment->getPolyline());
    }

    public function testHandleWithExistingSegment(): void
    {
        $output = new SpyOutput();
        
        // Create an existing segment first
        $existingSegment = \App\Tests\Domain\Strava\Segment\SegmentBuilder::fromDefaults()
            ->withSegmentId(\App\Domain\Strava\Segment\SegmentId::fromUnprefixed('789'))
            ->withPolyline('existingPolyline')
            ->build();
        $this->segmentRepository->add($existingSegment);

        // Setup activity with the same segment
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withName('Test activity with existing segment')
                ->withStartDateTime(SerializableDateTime::fromString('2025-01-01'))
                ->build(),
            [
                'segment_efforts' => [
                    [
                        'id' => '1',
                        'start_date_local' => '2023-07-29T09:34:03Z',
                        'name' => 'Test Segment Effort',
                        'elapsed_time' => 300,
                        'segment' => [
                            'id' => '789',
                            'name' => 'Test Segment',
                            'maximum_grade' => 5.3,
                            'distance' => 1000,
                        ],
                    ],
                ],
            ],
        ));

        // Strava API should not be called for existing segments
        $this->strava
            ->expects($this->never())
            ->method('getSegment');

        $this->commandBus->dispatch(new ImportSegments($output));

        // Verify existing segment polyline is unchanged
        $segment = $this->segmentRepository->find(\App\Domain\Strava\Segment\SegmentId::fromUnprefixed('789'));
        $this->assertEquals('existingPolyline', $segment->getPolyline());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->segmentRepository = $this->getContainer()->get(SegmentRepository::class);
        
        // Create mock Strava and Logger, replace in container
        $this->strava = $this->createMock(Strava::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->getContainer()->set(Strava::class, $this->strava);
        $this->getContainer()->set(LoggerInterface::class, $this->logger);
    }
}