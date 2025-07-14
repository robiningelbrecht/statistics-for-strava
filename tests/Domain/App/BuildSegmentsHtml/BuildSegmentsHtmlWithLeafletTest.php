<?php

namespace App\Tests\Domain\App\BuildSegmentsHtml;

use App\Domain\App\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Leaflet\LeafletMap;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Strava\Segment\SegmentBuilder;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;

class BuildSegmentsHtmlWithLeafletTest extends ContainerTestCase
{
    private CommandBus $commandBus;
    private MockObject $leafletMap;
    private SegmentRepository $segmentRepository;
    private FilesystemOperator $buildStorage;

    public function testHandleWithSegmentPolyline(): void
    {
        // Create segment with polyline
        $segment = SegmentBuilder::fromDefaults()
            ->withPolyline('encodedPolylineData123')
            ->build();
        $this->segmentRepository->add($segment);

        // Mock LeafletMap methods
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getTileLayer')
            ->willReturn('https://example.com/tiles/{z}/{x}/{y}.png');
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getMinZoom')
            ->willReturn(1);
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getMaxZoom')
            ->willReturn(18);
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getOverlayImageUrl')
            ->willReturn(null);
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getBounds')
            ->willReturn([]);
        $this->leafletMap
            ->expects($this->atLeastOnce())
            ->method('getBackgroundColor')
            ->willReturn('#ffffff');

        $this->commandBus->dispatch(new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        // Verify segment HTML was generated with leaflet data
        $segmentHtml = $this->buildStorage->read('segment/segment-1.html');
        $this->assertStringContainsString('data-leaflet=', $segmentHtml);
        $this->assertStringContainsString('encodedPolylineData123', $segmentHtml);
    }

    public function testHandleWithoutSegmentPolyline(): void
    {
        // Create segment without polyline
        $segment = SegmentBuilder::fromDefaults()
            ->withPolyline(null)
            ->build();
        $this->segmentRepository->add($segment);

        // LeafletMap methods should not be called
        $this->leafletMap
            ->expects($this->never())
            ->method('getTileLayer');

        $this->commandBus->dispatch(new BuildSegmentsHtml(SerializableDateTime::fromString('2023-10-17 16:15:04')));

        // Verify segment HTML was generated without leaflet data
        $segmentHtml = $this->buildStorage->read('segment/segment-1.html');
        $this->assertStringNotContainsString('data-leaflet=', $segmentHtml);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->segmentRepository = $this->getContainer()->get(SegmentRepository::class);
        $this->buildStorage = $this->getContainer()->get('build.storage');
        
        // Create mock LeafletMap and replace in container
        $this->leafletMap = $this->createMock(LeafletMap::class);
        $this->getContainer()->set(LeafletMap::class, $this->leafletMap);
    }
}