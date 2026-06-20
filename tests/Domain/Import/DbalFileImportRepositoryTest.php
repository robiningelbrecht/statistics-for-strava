<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import;

use App\Domain\Import\DbalFileImportRepository;
use App\Domain\Import\FileImportId;
use App\Domain\Import\FileImportRepository;
use App\Tests\ContainerTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class DbalFileImportRepositoryTest extends ContainerTestCase
{
    private FileImportRepository $fileImportRepository;

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
