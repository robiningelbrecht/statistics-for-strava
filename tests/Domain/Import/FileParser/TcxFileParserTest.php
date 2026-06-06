<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser;

use App\Domain\Activity\ImportSource;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Import\FileParser\CouldNotParseActivityFile;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Domain\Import\FileParser\TcxFileParser;
use App\Infrastructure\ValueObject\String\Path;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use PHPUnit\Framework\TestCase;

class TcxFileParserTest extends TestCase
{
    private TcxFileParser $parser;

    public function testSupportedExtensions(): void
    {
        $this->assertSame('tcx', $this->parser->supportedExtension());
    }

    public function testParse(): void
    {
        $parsed = $this->parser->parse($this->rawFileFromFixture('activity.tcx'));

        $activity = $parsed->getActivity();
        $this->assertSame(ImportSource::TCX_FILE, $activity->getImportSource());
        $this->assertSame('activity', $activity->getName());
        $this->assertSame(SportType::RIDE, $activity->getSportType());
        $this->assertSame('Garmin Edge 530', $activity->getDeviceName());
        $this->assertSame('2021-09-08T00:00:00+00:00', $activity->getStartDate()->format(\DateTimeInterface::ATOM));

        $this->assertSame(0.05, $activity->getDistance()->toFloat());
        $this->assertSame(10, $activity->getElapsedTimeInSeconds());
        $this->assertSame(10, $activity->getMovingTimeInSeconds());
        $this->assertSame(42, $activity->getCalories());
        $this->assertSame(125, $activity->getAverageHeartRate());
        $this->assertSame(130, $activity->getMaxHeartRate());
        $this->assertSame(81, $activity->getAverageCadence());
        $this->assertSame(205, $activity->getAveragePower());
        $this->assertSame(210, $activity->getMaxPower());
        $this->assertSame(19.8, $activity->getAverageSpeed()->toFloat());
        $this->assertSame(21.6, $activity->getMaxSpeed()->toFloat());
        $this->assertNotNull($activity->getStartingCoordinate());

        $streams = $parsed->getStreams();
        $this->assertSame([0, 10], $streams->filterOnType(StreamType::TIME)?->getData());
        $this->assertSame([0.0, 50.0], $streams->filterOnType(StreamType::DISTANCE)?->getData());
        $this->assertSame([[45.0, 22.5], [45.1, 22.6]], $streams->filterOnType(StreamType::LAT_LNG)?->getData());
        $this->assertSame([100.0, 110.0], $streams->filterOnType(StreamType::ALTITUDE)?->getData());
        $this->assertSame([120, 130], $streams->filterOnType(StreamType::HEART_RATE)?->getData());
        $this->assertSame([80, 82], $streams->filterOnType(StreamType::CADENCE)?->getData());
        $this->assertSame([5.0, 6.0], $streams->filterOnType(StreamType::VELOCITY)?->getData());
        $this->assertSame([200, 210], $streams->filterOnType(StreamType::WATTS)?->getData());

        $startingCoordinate = $activity->getStartingCoordinate();
        $this->assertNotNull($startingCoordinate);
        $this->assertSame(45.0, $startingCoordinate->getLatitude()->toFloat());
        $this->assertSame(22.5, $startingCoordinate->getLongitude()->toFloat());

        $this->assertCount(1, $parsed->getLaps());
        $lap = $parsed->getLaps()->getFirst();
        $this->assertInstanceOf(ActivityLap::class, $lap);
        $this->assertSame(1, $lap->getLapNumber());
        $this->assertSame(50.0, $lap->getDistance()->toFloat());
        $this->assertSame(10, $lap->getElapsedTimeInSeconds());
        $this->assertSame(125, $lap->getAverageHeartRate());
        $this->assertSame($activity->getId(), $lap->getActivityId());
    }

    public function testParseEmptyContentsThrows(): void
    {
        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse(RawActivityFile::from(Path::fromString('does-not-exist.tcx'), ''));
    }

    public function testParseUnsupportedSportThrows(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <TrainingCenterDatabase xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2">
              <Activities>
                <Activity Sport="Skiing">
                  <Id>2021-09-08T00:00:00Z</Id>
                  <Lap StartTime="2021-09-08T00:00:00Z"><Track></Track></Lap>
                </Activity>
              </Activities>
            </TrainingCenterDatabase>
            XML;

        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse(RawActivityFile::from(Path::fromString('unsupported-sport.tcx'), $xml));
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

        $this->parser = new TcxFileParser(PausedClock::fromString('2023-10-17 16:15:04'));
    }
}
