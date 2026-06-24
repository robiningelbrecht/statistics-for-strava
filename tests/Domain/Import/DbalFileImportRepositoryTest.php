<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import;

use App\Domain\Activity\ActivityId;
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

    public function testDeleteForActivity(): void
    {
        $this->fileImportRepository->add(
            FileImportBuilder::fromDefaults()
                ->withFileImportId(FileImportId::fromUnprefixed('1'))
                ->withFileHash('hash-1')
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->build()
        );
        $this->fileImportRepository->add(
            FileImportBuilder::fromDefaults()
                ->withFileImportId(FileImportId::fromUnprefixed('2'))
                ->withFileHash('hash-2')
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->build()
        );

        $this->fileImportRepository->deleteForActivity(ActivityId::fromUnprefixed('1'));

        $this->assertSame(
            0,
            (int) $this->getConnection()->executeQuery(
                'SELECT COUNT(*) FROM FileImport WHERE activityId = :activityId',
                ['activityId' => (string) ActivityId::fromUnprefixed('1')]
            )->fetchOne()
        );
        $this->assertSame(
            1,
            (int) $this->getConnection()->executeQuery(
                'SELECT COUNT(*) FROM FileImport WHERE activityId = :activityId',
                ['activityId' => (string) ActivityId::fromUnprefixed('2')]
            )->fetchOne()
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
