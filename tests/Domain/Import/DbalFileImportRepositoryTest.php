<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Domain\Import\DbalFileImportRepository;
use App\Domain\Import\FileImportId;
use App\Domain\Import\FileImportRepository;
use App\Domain\Import\FileImports;
use App\Domain\Import\FileImportStatus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class DbalFileImportRepositoryTest extends ContainerTestCase
{
    private FileImportRepository $fileImportRepository;

    public function testAddAndFindAll(): void
    {
        $success = FileImportBuilder::fromDefaults()
            ->withFileImportId(FileImportId::fromUnprefixed('1'))
            ->withFileHash('hash-one')
            ->withFileContents("\x00\x01binary\xfffit-bytes\x00")
            ->withSource(ImportSource::FIT_FILE)
            ->withStatus(FileImportStatus::SUCCESS)
            ->withActivityId(ActivityId::fromUnprefixed('123'))
            ->withImportedOn(SerializableDateTime::fromString('2026-06-04 10:00:00'))
            ->build();
        $this->fileImportRepository->add($success);

        $failed = FileImportBuilder::fromDefaults()
            ->withFileImportId(FileImportId::fromUnprefixed('2'))
            ->withFileHash('hash-two')
            ->withSource(ImportSource::TCX_FILE)
            ->withStatus(FileImportStatus::FAILED)
            ->withErrorMessage('Could not parse file')
            ->withActivityId(null)
            ->withImportedOn(SerializableDateTime::fromString('2026-06-04 11:00:00'))
            ->build();
        $this->fileImportRepository->add($failed);

        $this->assertEquals(
            FileImports::fromArray([$failed, $success]),
            $this->fileImportRepository->findAll()
        );
    }

    public function testExistsForFileHash(): void
    {
        $fileImport = FileImportBuilder::fromDefaults()
            ->withFileHash('known-hash')
            ->build();
        $this->fileImportRepository->add($fileImport);

        $this->assertTrue($this->fileImportRepository->existsForFileHash('known-hash'));
        $this->assertFalse($this->fileImportRepository->existsForFileHash('unknown-hash'));
    }

    public function testItRejectsDuplicateFileHash(): void
    {
        $this->fileImportRepository->add(
            FileImportBuilder::fromDefaults()
                ->withFileImportId(FileImportId::fromUnprefixed('1'))
                ->withFileHash('duplicate-hash')
                ->build()
        );

        $this->expectException(UniqueConstraintViolationException::class);
        $this->fileImportRepository->add(
            FileImportBuilder::fromDefaults()
                ->withFileImportId(FileImportId::fromUnprefixed('2'))
                ->withFileHash('duplicate-hash')
                ->build()
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileImportRepository = new DbalFileImportRepository(
            $this->getConnection()
        );
    }
}
