<?php

namespace App\Tests\Domain\Activity\Image;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Image\ActivityBasedImageRepository;
use App\Domain\Activity\Image\ImageRepository;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\Years;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class ActivityBasedImageRepositoryTest extends ContainerTestCase
{
    private ImageRepository $imageRepository;

    public function testFindRandomFor(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('0'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-03-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withSportType(SportType::VIRTUAL_ROW)
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withSportType(SportType::RUN)
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->withoutLocalImagePaths()
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('3'))
                ->withSportType(SportType::RUN)
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->withLocalImagePaths('test')
                ->build(),
            []
        ));

        $this->assertEquals(
            ActivityId::fromUnprefixed('3'),
            $this->imageRepository->findRandomFor(
                sportTypes: SportTypes::thatSupportImagesForStravaRewind(),
                years: Years::fromArray([Year::fromInt(2024)]),
            )->getActivity()->getId()
        );
    }

    public function testFindRandomForItShouldThrow(): void
    {
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('0'))
                ->withStartDateTime(SerializableDateTime::fromString('2025-03-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withSportType(SportType::VIRTUAL_ROW)
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->build(),
            []
        ));
        $this->getContainer()->get(ActivityWithRawDataRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('2'))
                ->withSportType(SportType::RUN)
                ->withStartDateTime(SerializableDateTime::fromString('2024-03-01 00:00:00'))
                ->withoutLocalImagePaths()
                ->build(),
            []
        ));

        $this->expectExceptionObject(new EntityNotFound('Random image not found'));

        $this->imageRepository->findRandomFor(
            sportTypes: SportTypes::thatSupportImagesForStravaRewind(),
            years: Years::fromArray([Year::fromInt(2024)]),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageRepository = new ActivityBasedImageRepository(
            $this->getContainer()->get(ActivityRepository::class),
        );
    }
}
