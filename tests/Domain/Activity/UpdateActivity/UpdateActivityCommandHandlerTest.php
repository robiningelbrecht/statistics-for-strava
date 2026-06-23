<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\UpdateActivity\UpdateActivity;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class UpdateActivityCommandHandlerTest extends ContainerTestCase
{
    private CommandBus $commandBus;
    private ActivityRepository $activityRepository;

    public function testHandle(): void
    {
        $this->activityRepository->add(ActivityWithRawData::fromState(
            activity: ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Original activity')
                ->build(),
            rawData: [],
        ));

        $this->commandBus->dispatch(UpdateActivity::fromPayload([
            'activityId' => 'activity-1',
            'name' => 'Updated activity',
        ]));

        $activity = $this->activityRepository->find(ActivityId::fromUnprefixed('1'));
        $this->assertSame('Updated activity', $activity->getOriginalName());
    }

    public function testHandleThrowsWhenActivityNotFound(): void
    {
        $this->expectException(EntityNotFound::class);

        $this->commandBus->dispatch(UpdateActivity::fromPayload([
            'activityId' => 'activity-999',
            'name' => 'Updated activity',
        ]));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->activityRepository = $this->getContainer()->get(ActivityRepository::class);
    }
}
