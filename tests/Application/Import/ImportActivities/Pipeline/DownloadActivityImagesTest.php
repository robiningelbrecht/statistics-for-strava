<?php

namespace App\Tests\Application\Import\ImportActivities\Pipeline;

use App\Application\Import\ImportActivities\Pipeline\ActivityImportContext;
use App\Application\Import\ImportActivities\Pipeline\DownloadActivityImages;
use App\Domain\Activity\ActivityId;
use App\Domain\Strava\Strava;
use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class DownloadActivityImagesTest extends ContainerTestCase
{
    private DownloadActivityImages $downloadActivityImages;
    private SpyStrava $strava;

    public function testProcessWhenImagesShouldNotBeDownloaded(): void
    {
        $context = ActivityImportContext::create(
            activityId: ActivityId::fromUnprefixed(1),
            rawStravaData: ['total_photo_count' => 3],
            isNewActivity: false,
        )
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

    public function testProcessWhenClientExceptionIsThrown(): void
    {
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(1000);
        $this->strava->triggerExceptionOnNextCall();

        $context = ActivityImportContext::create(
            activityId: ActivityId::fromUnprefixed(1),
            rawStravaData: ['total_photo_count' => 3],
            isNewActivity: true,
        )
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
            $this->strava = $this->getContainer()->get(Strava::class),
            new Filesystem(new InMemoryFilesystemAdapter()),
            $this->getContainer()->get(UuidFactory::class)
        );
    }
}
