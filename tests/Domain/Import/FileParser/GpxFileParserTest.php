<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser;

use App\Domain\Activity\ImportSource;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\Math;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Import\FileParser\CouldNotParseActivityFile;
use App\Domain\Import\FileParser\GpxFileParser;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Infrastructure\ValueObject\String\Path;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use PHPUnit\Framework\TestCase;

class GpxFileParserTest extends TestCase
{
    private GpxFileParser $parser;

    public function testSupportedExtensions(): void
    {
        $this->assertSame('gpx', $this->parser->supportedExtension());
    }

    public function testParse(): void
    {
        $parsed = $this->parser->parse($this->rawFileFromFixture('activity.gpx'));

        $distance = Math::haversineDistance(45.0, 22.5, 45.001, 22.501);

        $activity = $parsed->getActivity();
        $this->assertSame(ImportSource::GPX_FILE, $activity->getImportSource());
        $this->assertSame('Morning Ride', $activity->getName());
        $this->assertSame(SportType::RIDE, $activity->getSportType());
        $this->assertSame('Garmin Edge 530', $activity->getDeviceName());
        $this->assertSame('2021-09-08T00:00:00+00:00', $activity->getStartDate()->format(\DateTimeInterface::ATOM));

        $this->assertEqualsWithDelta($distance / 1000, $activity->getDistance()->toFloat(), 0.001);
        $this->assertSame(10.0, $activity->getElevation()->toFloat());
        $this->assertSame(10, $activity->getElapsedTimeInSeconds());
        $this->assertSame(10, $activity->getMovingTimeInSeconds());
        $this->assertSame(42, $activity->getCalories());
        $this->assertSame(125, $activity->getAverageHeartRate());
        $this->assertSame(130, $activity->getMaxHeartRate());
        $this->assertSame(81, $activity->getAverageCadence());
        $this->assertSame(205, $activity->getAveragePower());
        $this->assertSame(210, $activity->getMaxPower());
        $this->assertNotNull($activity->getStartingCoordinate());

        $streams = $parsed->getStreams();
        $this->assertSame([0, 10], $streams->filterOnType(StreamType::TIME)?->getData());
        $this->assertSame([[45.0, 22.5], [45.001, 22.501]], $streams->filterOnType(StreamType::LAT_LNG)?->getData());
        $this->assertSame([100.0, 110.0], $streams->filterOnType(StreamType::ALTITUDE)?->getData());
        $this->assertSame([120, 130], $streams->filterOnType(StreamType::HEART_RATE)?->getData());
        $this->assertSame([80, 82], $streams->filterOnType(StreamType::CADENCE)?->getData());
        $this->assertSame([200, 210], $streams->filterOnType(StreamType::WATTS)?->getData());

        $startingCoordinate = $activity->getStartingCoordinate();
        $this->assertNotNull($startingCoordinate);
        $this->assertSame(45.0, $startingCoordinate->getLatitude()->toFloat());
        $this->assertSame(22.5, $startingCoordinate->getLongitude()->toFloat());

        $this->assertCount(1, $parsed->getLaps());
        $lap = $parsed->getLaps()->getFirst();
        $this->assertInstanceOf(ActivityLap::class, $lap);
        $this->assertSame(1, $lap->getLapNumber());
        $this->assertSame(10, $lap->getElapsedTimeInSeconds());
        $this->assertSame(125, $lap->getAverageHeartRate());
        $this->assertSame($activity->getId(), $lap->getActivityId());
    }

    public function testParseEmptyContentsThrows(): void
    {
        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse(RawActivityFile::from(Path::fromString('does-not-exist.gpx'), ''));
    }

    public function testParseUnknownSportDefaultsToWorkout(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">
              <trk>
                <type>Other</type>
                <trkseg>
                  <trkpt lat="45.0" lon="22.5">
                    <time>2021-09-08T00:00:00Z</time>
                  </trkpt>
                  <trkpt lat="45.001" lon="22.501">
                    <time>2021-09-08T00:00:10Z</time>
                  </trkpt>
                </trkseg>
              </trk>
            </gpx>
            XML;

        $parsed = $this->parser->parse(RawActivityFile::from(Path::fromString('other-sport.gpx'), $xml));

        $this->assertSame(SportType::WORKOUT, $parsed->getActivity()->getSportType());
    }

    public function testParseWithoutTimedTrackpointsThrows(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1">
              <trk>
                <trkseg>
                  <trkpt lat="45.0" lon="22.5"><ele>100</ele></trkpt>
                </trkseg>
              </trk>
            </gpx>
            XML;

        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse(RawActivityFile::from(Path::fromString('no-time.gpx'), $xml));
    }

    private function rawFileFromFixture(string $name): RawActivityFile
    {
        $path = __DIR__.'/fixtures/'.$name;
        $contents = file_get_contents($path);
        if (false === $contents) {
            self::fail(sprintf('Could not read fixture "%s"', $name));
        }

        return RawActivityFile::from(Path::fromString($path), $contents);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new GpxFileParser(PausedClock::fromString('2023-10-17 16:15:04'));
    }
}
