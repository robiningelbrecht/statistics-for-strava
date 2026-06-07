<?php

declare(strict_types=1);

namespace App\Tests\Application\Import\FileImport;

use App\Application\Import\FileImport\ImportActivityFiles;
use App\Application\Import\FileImport\ImportActivityFilesCommandHandler;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ImportSource;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Import\FileImportRepository;
use App\Domain\Import\FileImportStatus;
use App\Tests\ContainerTestCase;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;

class ImportActivityFilesCommandHandlerTest extends ContainerTestCase
{
    private ImportActivityFilesCommandHandler $handler;
    private FilesystemOperator $watchStorage;

    public function testHandle(): void
    {
        $this->dropInWatchFolder('ride.tcx', $this->fixture('activity.tcx'));

        $output = new SpyOutput();
        $this->handler->handle(new ImportActivityFiles($output));

        $fileImports = $this->getContainer()->get(FileImportRepository::class)->findAll();
        $this->assertCount(1, $fileImports);
        $fileImport = $fileImports->getFirst();
        $this->assertSame(FileImportStatus::SUCCESS, $fileImport->getStatus());
        $this->assertSame('ride.tcx', $fileImport->getOriginalFilename());
        $this->assertSame($this->fixture('activity.tcx'), $fileImport->getFileContents());

        $activityId = $fileImport->getActivityId();
        $this->assertNotNull($activityId);
        $activity = $this->getContainer()->get(ActivityRepository::class)->find($activityId);

        $this->assertSame(ImportSource::TCX_FILE, $activity->getImportSource());
        $this->assertSame('ride', $activity->getName());
        $this->assertSame('Garmin Edge 530', $activity->getDeviceName());
        $this->assertNotNull($activity->getStartingCoordinate());
        $this->assertNotNull($activity->getEncodedPolyline());

        $this->assertCount(1, $this->getContainer()->get(ActivityLapRepository::class)->findBy($activityId));

        $streams = $this->getContainer()->get(ActivityStreamRepository::class)->findByActivityId($activityId);
        $this->assertNotNull($streams->filterOnType(StreamType::HEART_RATE));
        $this->assertNotNull($streams->filterOnType(StreamType::LAT_LNG));
    }

    public function testHandleIsIdempotent(): void
    {
        $bytes = $this->fixture('activity.tcx');

        $this->dropInWatchFolder('ride.tcx', $bytes);
        $this->handler->handle(new ImportActivityFiles(new SpyOutput()));

        $this->dropInWatchFolder('ride.tcx', $bytes);
        $output = new SpyOutput();
        $this->handler->handle(new ImportActivityFiles($output));

        $this->assertStringContainsString('already imported', (string) $output);
        $this->assertCount(1, $this->getContainer()->get(FileImportRepository::class)->findAll());
    }

    public function testHandleRecordsFailureForCorruptFile(): void
    {
        $this->dropInWatchFolder('broken.tcx', 'this is not valid xml');

        $output = new SpyOutput();
        $this->handler->handle(new ImportActivityFiles($output));

        $fileImports = $this->getContainer()->get(FileImportRepository::class)->findAll();
        $this->assertCount(1, $fileImports);
        $this->assertSame(FileImportStatus::FAILED, $fileImports->getFirst()->getStatus());
        $this->assertNull($fileImports->getFirst()->getActivityId());
        $this->assertSame('this is not valid xml', $fileImports->getFirst()->getFileContents());
    }

    public function testHandleWithoutImportDirectoryIsNoOp(): void
    {
        $output = new SpyOutput();
        $this->handler->handle(new ImportActivityFiles($output));

        $this->assertStringContainsString('nothing to import', (string) $output);
        $this->assertCount(0, $this->getContainer()->get(FileImportRepository::class)->findAll());
    }

    private function dropInWatchFolder(string $filename, string $contents): void
    {
        $this->watchStorage->write('watch/'.$filename, $contents);
    }

    private function fixture(string $name): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/Domain/Import/FileParser/fixtures/'.$name);
        if (false === $contents) {
            self::fail(sprintf('Could not read fixture "%s"', $name));
        }

        return $contents;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getContainer()->get(ImportActivityFilesCommandHandler::class);
        $this->watchStorage = $this->getContainer()->get('default.storage');
    }

    protected function tearDown(): void
    {
        $this->watchStorage->deleteDirectory('watch');

        parent::tearDown();
    }
}
