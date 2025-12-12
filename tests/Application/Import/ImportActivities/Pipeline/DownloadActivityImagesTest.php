<?php

namespace App\Tests\Application\Import\ImportActivities\Pipeline;

use App\Application\Import\ImportActivities\Pipeline\ActivityImportContext;
use App\Application\Import\ImportActivities\Pipeline\DownloadActivityImages;
use App\Domain\Gear\Gears;
use App\Domain\Strava\Strava;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class DownloadActivityImagesTest extends ContainerTestCase
{
    private DownloadActivityImages $downloadActivityImages;

    public function testProcessWhenImagesShouldNotBeDownloaded(): void
    {
        $context = ActivityImportContext::create(['total_photo_count' => 3], Gears::empty())
            ->withIsNewActivity(false)
            ->withActivity(
                ActivityBuilder::fromDefaults()
                    ->withLocalImagePaths('one', 'two', 'three')
                    ->build()
            );

        $this->assertEquals(
            $context,
            $this->downloadActivityImages->process($context)
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->downloadActivityImages = new DownloadActivityImages(
            $this->getContainer()->get(Strava::class),
            new Filesystem(new InMemoryFilesystemAdapter()),
            $this->getContainer()->get(UuidFactory::class)
        );
    }
}
