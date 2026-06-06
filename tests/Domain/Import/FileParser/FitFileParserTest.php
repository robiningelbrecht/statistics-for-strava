<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser;

use App\Domain\Activity\ImportSource;
use App\Domain\Activity\Lap\ActivityLap;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Import\FileParser\CouldNotParseActivityFile;
use App\Domain\Import\FileParser\FitFileParser;
use App\Domain\Import\FileParser\ParsedActivityFile;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Infrastructure\Process\ProcessFactory;
use App\Infrastructure\Process\SymfonyProcessFactory;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Path;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class FitFileParserTest extends TestCase
{
    private const int FIT_EPOCH_OFFSET = 631065600;
    private const int START_FIT_SECONDS = 1000000000;

    private FitFileParser $parser;
    private Stub $processFactory;

    public function testSupportedExtensions(): void
    {
        $this->assertSame('fit', $this->parser->supportedExtension());
    }

    public function testParse(): void
    {
        $this->givenFitToolReturns(Json::encode($this->fitDocument()));

        $parsed = $this->parser->parse($this->rawFile('/tmp/activity.fit'));

        $this->assertSame('Edge 530', $parsed->getActivity()->getDeviceName());
        $this->assertSame(self::FIT_EPOCH_OFFSET + self::START_FIT_SECONDS, $parsed->getActivity()->getStartDate()->getTimestamp());
        $this->assertParsedActivity($parsed);
    }

    public function testParseRealFitFileThroughBinary(): void
    {
        $parser = new FitFileParser(new SymfonyProcessFactory(), PausedClock::fromString('2023-10-17 16:15:04'));

        $parsed = $parser->parse($this->rawFileFromFixture('activity.fit'));

        $this->assertNull($parsed->getActivity()->getDeviceName());
        $this->assertSame('2021-09-08T00:00:00+00:00', $parsed->getActivity()->getStartDate()->format(\DateTimeInterface::ATOM));
        $this->assertParsedActivity($parsed);
    }

    private function assertParsedActivity(ParsedActivityFile $parsed): void
    {
        $activity = $parsed->getActivity();

        $this->assertSame(ImportSource::FIT_FILE, $activity->getImportSource());
        $this->assertSame('activity', $activity->getName());
        $this->assertSame(SportType::RIDE, $activity->getSportType());
        $this->assertSame(0.05, $activity->getDistance()->toFloat());
        $this->assertSame(10.0, $activity->getElevation()->toFloat());
        $this->assertSame(10, $activity->getMovingTimeInSeconds());
        $this->assertSame(12, $activity->getElapsedTimeInSeconds());
        $this->assertSame(18.0, $activity->getAverageSpeed()->toFloat());
        $this->assertSame(21.6, $activity->getMaxSpeed()->toFloat());
        $this->assertSame(125, $activity->getAverageHeartRate());
        $this->assertSame(140, $activity->getMaxHeartRate());
        $this->assertSame(82, $activity->getAverageCadence());
        $this->assertSame(205, $activity->getAveragePower());
        $this->assertSame(300, $activity->getMaxPower());
        $this->assertSame(42, $activity->getCalories());
        $this->assertSame(210, $activity->getKilojoules());
        $this->assertNotNull($activity->getEncodedPolyline());

        $startingCoordinate = $activity->getStartingCoordinate();
        $this->assertNotNull($startingCoordinate);
        $this->assertSame(45.0, $startingCoordinate->getLatitude()->toFloat());
        $this->assertSame(22.5, $startingCoordinate->getLongitude()->toFloat());

        $streams = $parsed->getStreams();
        $this->assertSame([0, 10], $streams->filterOnType(StreamType::TIME)?->getData());
        $this->assertSame([0.0, 50.0], $streams->filterOnType(StreamType::DISTANCE)?->getData());
        $this->assertSame([[0.0, 0.0], [45.0, 22.5]], $streams->filterOnType(StreamType::LAT_LNG)?->getData());
        $this->assertSame([100.0, 110.0], $streams->filterOnType(StreamType::ALTITUDE)?->getData());
        $this->assertSame([5.0, 6.0], $streams->filterOnType(StreamType::VELOCITY)?->getData());
        $this->assertSame([120, 130], $streams->filterOnType(StreamType::HEART_RATE)?->getData());
        $this->assertSame([80, 84], $streams->filterOnType(StreamType::CADENCE)?->getData());
        $this->assertSame([200, 250], $streams->filterOnType(StreamType::WATTS)?->getData());
        $this->assertSame([20, 21], $streams->filterOnType(StreamType::TEMP)?->getData());

        $this->assertCount(1, $parsed->getLaps());
        $lap = $parsed->getLaps()->getFirst();
        $this->assertInstanceOf(ActivityLap::class, $lap);
        $this->assertSame(50.0, $lap->getDistance()->toFloat());
        $this->assertSame(10, $lap->getMovingTimeInSeconds());
        $this->assertSame(125, $lap->getAverageHeartRate());
        $this->assertSame($activity->getId(), $lap->getActivityId());
    }

    public function testParseTrailRunSubSport(): void
    {
        $document = $this->fitDocument();
        foreach ($document['files'][0]['messages'] as &$message) {
            if ('session' === $message['name']) {
                $message['fields'] = [
                    ['name' => 'sport', 'value' => 1], // running
                    ['name' => 'sub_sport', 'value' => 3], // trail
                    ['name' => 'start_time', 'value' => self::START_FIT_SECONDS],
                ];
            }
        }
        unset($message);
        $this->givenFitToolReturns(Json::encode($document));

        $this->assertSame(SportType::TRAIL_RUN, $this->parser->parse($this->rawFile('/tmp/activity.fit'))->getActivity()->getSportType());
    }

    public function testParseUnsuccessfulProcessThrows(): void
    {
        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn(false);
        $process->method('getErrorOutput')->willReturn('boom');
        $this->processFactory->method('create')->willReturn($process);

        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse($this->rawFile('/tmp/activity.fit'));
    }

    public function testParseUnsupportedSportThrows(): void
    {
        $document = $this->fitDocument();
        foreach ($document['files'][0]['messages'] as &$message) {
            if ('session' === $message['name']) {
                $message['fields'] = [
                    ['name' => 'sport', 'value' => 8], // tennis (unsupported)
                    ['name' => 'start_time', 'value' => self::START_FIT_SECONDS],
                ];
            }
        }
        unset($message);
        $this->givenFitToolReturns(Json::encode($document));

        $this->expectException(CouldNotParseActivityFile::class);
        $this->parser->parse($this->rawFile('/tmp/activity.fit'));
    }

    private function rawFile(string $path): RawActivityFile
    {
        return RawActivityFile::from(Path::fromString($path), '');
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

    private function givenFitToolReturns(string $output): void
    {
        $process = $this->createStub(Process::class);
        $process->method('isSuccessful')->willReturn(true);
        $process->method('getOutput')->willReturn($output);

        $this->processFactory
            ->method('create')
            ->willReturn($process);
    }

    private function fitDocument(): array
    {
        $lat45 = 2 ** 29; // 45 degrees in semicircles
        $lng225 = 2 ** 28; // 22.5 degrees in semicircles

        return [
            'files' => [[
                'profileVersion' => 2132,
                'messages' => [
                    ['name' => 'file_id', 'fields' => [
                        ['name' => 'product_name', 'value' => 'Edge 530'],
                    ]],
                    ['name' => 'session', 'fields' => [
                        ['name' => 'sport', 'value' => 2], // cycling
                        ['name' => 'sub_sport', 'value' => 7], // road
                        ['name' => 'start_time', 'value' => self::START_FIT_SECONDS],
                        ['name' => 'start_position_lat', 'value' => $lat45],
                        ['name' => 'start_position_long', 'value' => $lng225],
                        ['name' => 'total_distance', 'value' => 50.0],
                        ['name' => 'total_ascent', 'value' => 10],
                        ['name' => 'total_timer_time', 'value' => 10],
                        ['name' => 'total_elapsed_time', 'value' => 12],
                        ['name' => 'enhanced_avg_speed', 'value' => 5.0],
                        ['name' => 'enhanced_max_speed', 'value' => 6.0],
                        ['name' => 'avg_heart_rate', 'value' => 125],
                        ['name' => 'max_heart_rate', 'value' => 140],
                        ['name' => 'avg_cadence', 'value' => 82],
                        ['name' => 'avg_power', 'value' => 205],
                        ['name' => 'max_power', 'value' => 300],
                        ['name' => 'total_calories', 'value' => 42],
                        ['name' => 'total_work', 'value' => 210000],
                    ]],
                    ['name' => 'lap', 'fields' => [
                        ['name' => 'start_time', 'value' => self::START_FIT_SECONDS],
                        ['name' => 'total_distance', 'value' => 50.0],
                        ['name' => 'total_timer_time', 'value' => 10],
                        ['name' => 'total_elapsed_time', 'value' => 12],
                        ['name' => 'enhanced_avg_speed', 'value' => 5.0],
                        ['name' => 'enhanced_max_speed', 'value' => 6.0],
                        ['name' => 'total_ascent', 'value' => 10],
                        ['name' => 'avg_heart_rate', 'value' => 125],
                    ]],
                    ['name' => 'record', 'fields' => [
                        ['name' => 'timestamp', 'value' => self::START_FIT_SECONDS],
                        ['name' => 'distance', 'value' => 0.0],
                        ['name' => 'position_lat', 'value' => 0],
                        ['name' => 'position_long', 'value' => 0],
                        ['name' => 'enhanced_altitude', 'value' => 100.0],
                        ['name' => 'enhanced_speed', 'value' => 5.0],
                        ['name' => 'heart_rate', 'value' => 120],
                        ['name' => 'cadence', 'value' => 80],
                        ['name' => 'power', 'value' => 200],
                        ['name' => 'temperature', 'value' => 20],
                    ]],
                    ['name' => 'record', 'fields' => [
                        ['name' => 'timestamp', 'value' => self::START_FIT_SECONDS + 10],
                        ['name' => 'distance', 'value' => 50.0],
                        ['name' => 'position_lat', 'value' => $lat45],
                        ['name' => 'position_long', 'value' => $lng225],
                        ['name' => 'enhanced_altitude', 'value' => 110.0],
                        ['name' => 'enhanced_speed', 'value' => 6.0],
                        ['name' => 'heart_rate', 'value' => 130],
                        ['name' => 'cadence', 'value' => 84],
                        ['name' => 'power', 'value' => 250],
                        ['name' => 'temperature', 'value' => 21],
                    ]],
                ],
            ]],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new FitFileParser(
            $this->processFactory = $this->createStub(ProcessFactory::class),
            PausedClock::fromString('2023-10-17 16:15:04'),
        );
    }
}
