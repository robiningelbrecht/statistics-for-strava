<?php

namespace App\Tests\Domain\Integration\Notification\SendNotification;

use App\Domain\Integration\Notification\SendNotification\SendNotification;
use App\Domain\Integration\Notification\Shoutrrr\Shoutrrr;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Url;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Integration\Notification\Shoutrrr\SpyShoutrrr;
use Spatie\Snapshots\MatchesSnapshots;

class SendNotificationCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->commandBus->dispatch(new SendNotification(
            title: 'le title',
            message: 'le message',
            tags: ['tag1', 'tag2'],
            actionUrl: Url::fromString('https://localhost'),
        ));

        /** @var SpyShoutrrr $shoutrrr */
        $shoutrrr = $this->getContainer()->get(Shoutrrr::class);
        $this->assertMatchesJsonSnapshot(Json::encode($shoutrrr->getNotifications()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
