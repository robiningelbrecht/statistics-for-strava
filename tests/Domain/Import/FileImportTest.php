<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Domain\Import\FileImport;
use App\Domain\Import\FileImportId;
use App\Domain\Import\FileImportStatus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\TestCase;

class FileImportTest extends TestCase
{
    public function testCreate(): void
    {
        $fileImportId = FileImportId::fromUnprefixed('abc');

        $fileImport = FileImport::create(
            fileImportId: $fileImportId,
            originalFilename: 'ride.fit',
            fileHash: 'hash',
            fileContents: 'raw-fit-bytes',
            source: ImportSource::FIT_FILE,
            status: FileImportStatus::SUCCESS,
            errorMessage: null,
            activityId: ActivityId::fromUnprefixed('123'),
            importedOn: SerializableDateTime::fromString('2026-06-04 10:00:00'),
        );

        $this->assertEquals($fileImportId, $fileImport->getId());
    }

    public function testFromStateRoundTrips(): void
    {
        $fileImportId = FileImportId::fromUnprefixed('abc');
        $activityId = ActivityId::fromUnprefixed('123');
        $importedOn = SerializableDateTime::fromString('2026-06-04 10:00:00');

        $fileImport = FileImport::fromState(
            fileImportId: $fileImportId,
            originalFilename: 'ride.tcx',
            fileHash: 'the-hash',
            fileContents: 'raw-tcx-bytes',
            source: ImportSource::TCX_FILE,
            status: FileImportStatus::FAILED,
            errorMessage: 'Could not parse file',
            activityId: $activityId,
            importedOn: $importedOn,
        );

        $this->assertEquals($fileImportId, $fileImport->getId());
        $this->assertEquals('ride.tcx', $fileImport->getOriginalFilename());
        $this->assertEquals('the-hash', $fileImport->getFileHash());
        $this->assertEquals('raw-tcx-bytes', $fileImport->getFileContents());
        $this->assertEquals(ImportSource::TCX_FILE, $fileImport->getSource());
        $this->assertEquals(FileImportStatus::FAILED, $fileImport->getStatus());
        $this->assertEquals('Could not parse file', $fileImport->getErrorMessage());
        $this->assertEquals($activityId, $fileImport->getActivityId());
        $this->assertEquals($importedOn, $fileImport->getImportedOn());
    }
}
